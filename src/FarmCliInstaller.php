<?php

namespace TuleapWikiFarm;

use MediaWiki\Installer\CliInstaller;
use MediaWiki\Installer\DatabaseInstaller;

class FarmCliInstaller extends CliInstaller {

	/**
	 * @param string $siteName
	 * @param string|null $admin
	 * @param array $options
	 *
	 * @throws \MediaWiki\Installer\InstallException
	 */
	public function __construct( $siteName, $admin = null, array $options = [] ) {
		parent::__construct( $siteName, $admin,	$options );
		if ( isset( $options['dbssl'] ) ) {
			$this->setVar( 'wgDBssl', true );
		}
	}

	/**
	 * Get an instance of DatabaseInstaller for the specified DB type.
	 *
	 * @param mixed $type DB installer for which is needed, false to use default.
	 *
	 * @return DatabaseInstaller
	 */
	public function getDBInstaller( $type = false ) {
		if ( $this->getVar( 'wgDBssl' ) ) {
			$this->dbInstallers[$type] = new MySQLSSLInstaller( $this );
			return $this->dbInstallers[$type];
		}
		return parent::getDBInstaller( $type );
	}
}
