<?php

use MediaWiki\MediaWikiServices;
use Symfony\Component\Process\Process;
use TuleapWikiFarm\InstanceEntity;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class MigrateInstance extends Maintenance {

	/** @var \TuleapWikiFarm\InstanceManager */
	private $manager;
	/** @var string */
	private $phpCli;

	public function __construct() {
		parent::__construct();
		$this->addOption( 'skip-registration', 'Skip registration of the instance in the database' );
		$this->addOption( 'show-update-output', 'Show the output of the update.php script' );
		$this->addOption( 'projectname', '', true, true );
		$this->addOption( 'groupid', '', false, true );
		$this->addOption( 'dbprefix', '', false, true );
	}

	/**
	 * @return bool|void|null
	 * @throws Exception
	 */
	public function execute() {
		/** @var \TuleapWikiFarm\InstanceManager $this->manager */
		$this->manager = MediaWikiServices::getInstance()->getService( 'InstanceManager' );

		$this->phpCli = MediaWikiServices::getInstance()->getMainConfig()->get( 'PhpCli' );

		if ( $this->hasOption( 'skip-registration' ) ) {
			$entity = $this->manager->getStore()->getInstanceByName( $this->getOption( 'projectname' ) );
		} else {
			$entity = $this->registerInstance();
		}
		if ( !( $entity instanceof InstanceEntity ) ) {
			$this->fatalError( 'Instance not found' );
		}

		$this->migrateDatabase( $entity );
		$this->setInstanceStatus( $entity );
	}

	/**
	 * @return InstanceEntity
	 * @throws Exception
	 */
	private function registerInstance() {
		$name = $this->getOption( 'projectname', '' );
		if ( !$name ) {
			$this->fatalError( 'Param --projectname must have a value' );
		}
		$groupId = (int)$this->getOption( 'groupid', 0 );
		if ( !$groupId ) {
			$this->fatalError( 'Param --groupid must have an integer value' );
		}

		if ( !$this->manager->isCreatable( $name ) ) {
			$this->fatalError( 'Instance with this name already exists (migrated or newly created)' );
		}

		$dbPrefix = $this->getOption( 'dbprefix' );
		if ( $this->manager->getCentralDb() !== null && !$dbPrefix ) {
			$this->fatalError(
				'When configured to use central database, param dbprefix must be set'
			);
		}
		if ( $this->manager->isProjectIdAssigned( $groupId, $dbPrefix ) ) {
			$this->fatalError( 'Instance for this groupid or dbprefix already exists' );
		}

		$this->output( "Registering instance...\n" );
		$entity = $this->manager->getNewInstance( $name, $groupId );
		if ( $dbPrefix ) {
			$entity->setDatabasePrefix( $dbPrefix );
		}
		$entity->setDirectory( $this->manager->generateInstanceDirectoryName( $entity ) );
		$entity->setScriptPath( $this->manager->generateScriptPath( $entity ) );
		$entity->setDatabaseName( $this->manager->generateDbName( $entity ) );
		$entity->setStatus( InstanceEntity::STATE_MIGRATION );
		if ( !$this->manager->getStore()->storeEntity( $entity ) ) {
			$this->fatalError( "Could not register instance\n" );
		}
		$this->output( "Instance registered\n" );
		return $entity;
	}

	/**
	 * @param InstanceEntity $instance
	 *
	 * @return void
	 */
	private function migrateDatabase( InstanceEntity $instance ) {
		$this->output( "Migrating database...\n" );
		$process = new Process( array_merge(
			[
				$this->phpCli, $GLOBALS['IP'] . '/maintenance/update.php',
			],
			[ '--quick' ],
			[ '--sfr', $instance->getName() ]
		) );
		$process->setTimeout( null );
		$process->run();
		if ( $process->getExitCode() ) {
			$this->fatalError( "Could not migrate database: " . $process->getErrorOutput() . "\n" );
		}
		if ( $this->getOption( 'show-update-output' ) ) {
			$this->output( $process->getOutput() );
			$this->output( "----------------------------------------\n" );
		}
		$this->output( "Database migrated\n" );
	}

	/**
	 * @param InstanceEntity $instance
	 *
	 * @return void
	 * @throws Exception
	 */
	private function setInstanceStatus( InstanceEntity $instance ) {
		$this->output( "Setting instance status to 'normal'...\n" );
		$instance->setStatus( InstanceEntity::STATE_READY );
		if ( !$this->manager->getStore()->storeEntity( $instance ) ) {
			$this->fatalError( "Could not set instance status after migration\n" );
		}
		$this->output( "Instance status set to 'normal'. Done\n" );
	}
}

$maintClass = 'MigrateInstance';
require_once RUN_MAINTENANCE_IF_MAIN;
