<?php

use MediaWiki\Installer\InstallException;
use TuleapWikiFarm\FarmCliInstaller;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

define( 'MW_CONFIG_CALLBACK', 'Installer::overrideConfig' );
define( 'MEDIAWIKI_INSTALL', true );

/**
 * Customized installer for Tuleap Wiki Farm
 *
 * Added features:
 * - new db type - dbtype = mysqlssl - for SSL connection to MySQL
 *
 * Not available features:
 * - --passfile
 * - --dbpassfile
 * - --dbport (was only for PostgreSQL)
 * - --dbpath (was only for SQLite)
 * - --env-checks
 * - --confpath
 * - --dbschema (was only for PostgreSQL/Microsoft SQL Server)
 */
class InstallFarm extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addDescription( "CLI-based MediaWiki installation and configuration.\n" .
			"Default options are indicated in parentheses." );

		$this->addArg( 'name', 'The name of the wiki (MediaWiki)', false );

		$this->addArg( 'admin', 'The username of the wiki administrator.' );
		$this->addOption( 'pass', 'The password for the wiki administrator.', false, true );
		/* $this->addOption( 'email', 'The email for the wiki administrator', false, true ); */
		$this->addOption(
			'scriptpath',
			'The relative path of the wiki in the web server (/wiki)',
			false,
			true
		);
		$this->addOption(
			'server',
			'The base URL of the web server the wiki will be on (http://localhost)',
			false,
			true
		);

		$this->addOption( 'lang', 'The language to use (en)', false, true );
		/* $this->addOption( 'cont-lang', 'The content language (en)', false, true ); */

		$this->addOption( 'dbtype', 'The type of database (mysql)', false, true );
		$this->addOption( 'dbserver', 'The database host (localhost)', false, true );
		$this->addOption( 'dbname', 'The database name (my_wiki)', false, true );
		$this->addOption( 'dbprefix', 'Optional database table name prefix', false, true );
		$this->addOption( 'installdbuser', 'The user to use for installing (root)', false, true );
		$this->addOption( 'installdbpass', 'The password for the DB user to install as.', false, true );
		$this->addOption( 'dbuser', 'The user to use for normal operations (wikiuser)', false, true );
		$this->addOption( 'dbpass', 'The password for the DB user for normal operations', false, true );
		$this->addOption( 'with-extensions', "Detect and include extensions" );
		$this->addOption( 'extensions', 'Comma-separated list of extensions to install',
			false, true, false, true );
		$this->addOption( 'skins', 'Comma-separated list of skins to install (default: all)',
			false, true, false, true );
		$this->addOption( 'dbssl', 'Use SSL connection to MySQL' );
	}

	public function execute() {
		// phpcs:ignore MediaWiki.NamingConventions.ValidGlobalName.allowedPrefix
		global $IP;
		// Cannot be required earlier
		require_once dirname( __DIR__ ) . '/src/FarmCliInstaller.php';
		require_once dirname( __DIR__ ) . '/src/MySQLSSLInstaller.php';

		$siteName = $this->getArg( 0, 'MediaWiki' );
		$adminName = $this->getArg( 1 );

		try {
			$installer = new FarmCliInstaller( $siteName, $adminName, $this->mOptions );
		} catch ( InstallException $e ) {
			$this->output( $e->getStatus()->getMessage( false, false, 'en' )->text() . "\n" );
			return false;
		}

		$status = $installer->doEnvironmentChecks();
		if ( $status->isGood() ) {
			$installer->showMessage( 'config-env-good' );
		} else {
			$installer->showStatusMessage( $status );

			return false;
		}
		$status = $installer->execute();
		if ( !$status->isGood() ) {
			$installer->showStatusMessage( $status );

			return false;
		}
		$installer->writeConfigurationFile( $this->getOption( 'confpath', $IP ) );
		$installer->showMessage(
			'config-install-success',
			$installer->getVar( 'wgServer' ),
			$installer->getVar( 'wgScriptPath' )
		);
		return true;
	}
}

$maintClass = InstallFarm::class;
require_once RUN_MAINTENANCE_IF_MAIN;
