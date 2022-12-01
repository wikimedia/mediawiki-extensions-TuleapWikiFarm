<?php

use MediaWiki\Installer\InstallException;
use TuleapWikiFarm\InstanceCliInstaller;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

define( 'MW_CONFIG_CALLBACK', 'Installer::overrideConfig' );
define( 'MEDIAWIKI_INSTALL', true );

class InstallInstance extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addOption( 'instanceName', '', true, true );
		$this->addOption( 'dbserver', '', true, true );
		$this->addOption( 'dbname', '', true, true );
		$this->addOption( 'dbprefix', '', true, true );
		$this->addOption( 'dbuser', '', true, true );
		$this->addOption( 'dbpass', '', true, true );
		$this->addOption( 'server', '', true, true );
		$this->addOption( 'scriptpath', '', true, true );
		$this->addOption( 'lang', '', true, true );
		$this->addOption( 'adminuser', '', true, true );
		$this->addOption( 'adminpass', '', true, true );
		$this->addOption( 'dbssl', 'Enable SSL connection on DB' );
	}

	/**
	 * @return bool|void|null
	 * @throws InstallException
	 */
	public function execute() {
		$options = [
			'scriptpath' => $this->getOption( 'scriptpath' ),
			'dbname' => $this->getOption( 'dbname' ),
			'dbprefix' => $this->getOption( 'dbprefix' ),
			'dbserver' => $this->getOption( 'dbserver' ),
			'dbuser' => $this->getOption( 'dbuser' ),
			'dbpass' => $this->getOption( 'dbpass' ),
			'server' => $this->getOption( 'server' ),
			'pass' => $this->getOption( 'adminpass' ),
			'lang' => $this->getOption( 'lang' ),
		];
		if ( $this->hasOption( 'dbssl' ) ) {
			$options['dbssl'] = true;
		}
		$installer = new InstanceCliInstaller(
			$this->getOption( 'instanceName' ), $this->getOption( 'adminuser' ), $options
		);

		$status = $installer->execute();

		if ( !$status->isOk() ) {
			$this->fatalError( $status->getMessage()->plain() );
		}
	}

}

$maintClass = InstallInstance::class;
require_once RUN_MAINTENANCE_IF_MAIN;
