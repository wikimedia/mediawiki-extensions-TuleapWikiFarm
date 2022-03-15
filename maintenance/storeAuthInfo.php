<?php

use MediaWiki\Installer\InstallException;
use MediaWiki\MediaWikiServices;
use TuleapWikiFarm\GlobalStorage;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

/**
 * This script is meant to be called internally, during auth process
 * DO NOT CALL DIRECTLY
 */
class StoreAuthInfo extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'instanceName', '', true, true );
		$this->addOption( 'state', '', true, true );
	}

	/**
	 * @return bool|void|null
	 * @throws InstallException
	 */
	public function execute() {
		try {
			$globalStorage = new GlobalStorage(
				MediaWikiServices::getInstance()->getDBLoadBalancer()
			);
			$res = $globalStorage->setAuthRecord(
				$this->getOption( 'state' ),
				$this->getOption( 'instanceName' )
			);
		} catch ( Exception $ex ) {
			$this->error( $ex->getMessage(), 1 );
		}

		if ( !$res ) {
			$this->error( 'Could not insert record', 1 );
		}
	}

}

$maintClass = 'StoreAuthInfo';
require_once RUN_MAINTENANCE_IF_MAIN;
