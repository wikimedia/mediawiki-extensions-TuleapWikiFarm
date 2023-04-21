<?php

use MediaWiki\MediaWikiServices;
use Symfony\Component\Process\Process;
use TuleapWikiFarm\InstanceEntity;
use TuleapWikiFarm\InstanceManager;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class RunForAll extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'script', '', true, true );
		$this->addOption( 'args', '', false, true );
		$this->addOption( 'set-maintenance', 'Set maintenance mode while script is running' );
	}

	public function execute() {
		/** @var InstanceManager $manager */
		$manager = MediaWikiServices::getInstance()->getService( 'InstanceManager' );
		$config = MediaWikiServices::getInstance()->getMainConfig();

		foreach ( $manager->getStore()->getInstanceNames() as $name ) {
			$instance = $manager->getStore()->getInstanceByName( $name );
			if (
				!$instance ||
				!in_array( $instance->getStatus(), [
					InstanceEntity::STATE_MIGRATION, InstanceEntity::STATE_READY
				] )
			) {
				throw new Exception( 'Instance not available or not ready', 400 );
			}
			$process = new Process( array_merge(
				[
					$config->get( 'PhpCli' ), $this->getOption( 'script' ),
				],
				explode( ' ', $this->getOption( 'args' ) ),
				[ '--sfr', $name ]
			) );
			$process->setTimeout( null );
			$this->output( "Executing for $name\n" );
			if ( $this->getOption( 'set-maintenance', false ) ) {
				$this->setMaintenanceMode( $instance, $manager );
			}
			$process->run();
			$this->setReady( $instance, $manager );
			if ( !$process->isSuccessful() ) {
				$this->error( $process->getErrorOutput() . "\n" );
			} else {
				$this->output( $process->getOutput() . "\n" );
			}
		}
	}

	/**
	 * @param InstanceEntity $instance
	 * @param InstanceManager $manager
	 *
	 * @return void
	 * @throws Exception
	 */
	public function setMaintenanceMode( InstanceEntity $instance, InstanceManager $manager ) {
		$instance->setStatus( InstanceEntity::STATE_MAINTENANCE );
		$manager->getStore()->storeEntity( $instance );
	}

	/**
	 * @param InstanceEntity $instance
	 * @param InstanceManager $manager
	 *
	 * @return void
	 * @throws Exception
	 */
	private function setReady( InstanceEntity $instance, InstanceManager $manager ) {
		$instance->setStatus( InstanceEntity::STATE_READY );
		$manager->getStore()->storeEntity( $instance );
	}
}

$maintClass = RunForAll::class;
require_once RUN_MAINTENANCE_IF_MAIN;
