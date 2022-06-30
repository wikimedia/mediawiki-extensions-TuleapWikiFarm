<?php

use MediaWiki\MediaWikiServices;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class RegisterInstance extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'projectname', '', true, true );
		$this->addOption( 'groupid', '', false, true );
		$this->addOption( 'dbprefix', '', false, true );
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

		$dbPrefix = $this->getOption( 'dbprefix' );
		if ( $manager->getUseSingleDb() && !$dbPrefix ) {
			$this->fatalError(
				'When configured to use single database, param dbprefix must be set'
			);
		}
		if ( $manager->isProjectIdAssigned( $groupId, $dbPrefix ) ) {
			$this->fatalError( 'Instance for this groupid or dbprefix already exists' );
		}

		$entity = $manager->getNewInstance( $name, $groupId );
		if ( $dbPrefix ) {
			$entity->setDatabasePrefix( $dbPrefix );
		}
		$entity->setDirectory( $manager->generateInstanceDirectoryName( $entity ) );
		$entity->setScriptPath( $manager->generateScriptPath( $entity ) );
		$entity->setDatabaseName( $manager->generateDbName( $entity ) );
		$entity->setStatus( \TuleapWikiFarm\InstanceEntity::STATE_READY );
		if ( !$manager->getStore()->storeEntity( $entity ) ) {
			$this->fatalError( "Could not register instance" );
		}
		$this->output( 'Instance registered' );
	}
}

$maintClass = 'RegisterInstance';
require_once RUN_MAINTENANCE_IF_MAIN;
