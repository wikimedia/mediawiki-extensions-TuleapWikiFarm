<?php

use MediaWiki\MediaWikiServices;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class RegisterInstance extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'projectname', '', true, true );
		$this->addOption( 'groupid', '', false, true );
	}

	public function execute() {
		$name = $this->getOption( 'projectname', '' );
		if ( !$name ) {
			$this->fatalError( 'Param --projectname must have a value' );
		}
		$groupId = (int)$this->getOption( 'groupid', 0 );
		if ( !$groupId ) {
			$this->fatalError( 'Param --groupid must have an integer value' );
		}
		/** @var \TuleapWikiFarm\InstanceManager $manager */
		$manager = MediaWikiServices::getInstance()->getService( 'InstanceManager' );
		if ( !$manager->isCreatable( $name ) ) {
			$this->fatalError( 'Instance with this name already exists' );
		}
		$entity = $manager->getStore()->getInstanceById( $groupId );
		if ( $entity !== null ) {
			$this->fatalError( 'Instance for this groupid already exists' );
		}

		$entity = $manager->getNewInstance( $name, $groupId );
		if ( !$manager->getStore()->storeEntity( $entity ) ) {
			$this->fatalError( "Could not register instance" );
		}
		$this->output( 'Instance registered' );
	}
}

$maintClass = 'RegisterInstance';
require_once RUN_MAINTENANCE_IF_MAIN;
