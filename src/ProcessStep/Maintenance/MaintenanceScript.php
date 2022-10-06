<?php

namespace TuleapWikiFarm\ProcessStep\Maintenance;

use Config;
use Exception;
use Symfony\Component\Process\Process;
use TuleapWikiFarm\InstanceEntity;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\IProcessStep;

abstract class MaintenanceScript implements IProcessStep {
	/** @var InstanceManager */
	private $manager;
	/** @var Config */
	protected $config;
	/** @var int */
	private $instanceId;
	/** @var array */
	protected $args;
	/** @var bool */
	protected $noOutput;

	/**
	 * @param InstanceManager $manager
	 * @param Config $config
	 * @param null $id
	 * @param array $args
	 * @param bool $noOutput
	 */
	public function __construct(
		InstanceManager $manager, Config $config, $id = null, $args = [], $noOutput = false
	) {
		$this->manager = $manager;
		$this->config = $config;
		$this->instanceId = $id;
		$this->args = $args;
		$this->noOutput = $noOutput;
	}

	/**
	 * @param array $data
	 * @return array
	 * @throws Exception
	 */
	public function execute( $data = [] ): array {
		if ( !$this->instanceId && isset( $data['id'] ) ) {
			$this->instanceId = $data['id'];
		}

		if ( $this->instanceId === -1 ) {
			$process = $this->runForAll();
			$process->run();
		} else {
			$instance = $this->manager->getStore()->getInstanceById( $this->instanceId );
			$process = $this->runForInstance( $instance );
			if ( $this->shouldSetMaintenanceMode() ) {
				$this->setMaintenanceMode( $instance );
			}
			$process->run();
			$this->setReady( $instance );
		}

		if ( !$process->isSuccessful() ) {
			throw new Exception( $process->getErrorOutput() );
		}

		if ( $this->noOutput ) {
			return [
				'id' => $this->instanceId,
				'warnings' => $data['warnings'] ?? [],
			];
		}

		return [
			'id' => $this->instanceId,
			'command' => $process->getCommandLine(),
			'stdout' => $process->getOutput(),
			'stderr' => $process->getErrorOutput(),
			'warnings' => $data['warnings'] ?? [],
		];
	}

	/**
	 * @param InstanceEntity $instance
	 *
	 * @return void
	 * @throws Exception
	 */
	public function setMaintenanceMode( InstanceEntity $instance ) {
		$instance->setStatus( InstanceEntity::STATE_MAINTENANCE );
		$this->manager->getStore()->storeEntity( $instance );
	}

	/**
	 * @param InstanceEntity $instance
	 *
	 * @return void
	 * @throws Exception
	 */
	private function setReady( InstanceEntity $instance ) {
		$instance->setStatus( InstanceEntity::STATE_READY );
		$this->manager->getStore()->storeEntity( $instance );
	}

	/**
	 * @return string|null
	 */
	private function getPhpExecutable() {
		return $this->config->get( 'PhpCli' );
	}

	/**
	 * @param InstanceEntity $instance
	 * @return Process
	 */
	private function runForInstance( InstanceEntity $instance ) {
		$process = new Process( array_merge(
			[
				$this->getPhpExecutable(), $this->getFullScriptPath(),
			],
			$this->getFormattedArgs(),
			[ '--sfr', $instance->getName() ]
		) );
		$this->modifyProcess( $process );

		return $process;
	}

	/**
	 * @return Process
	 */
	private function runForAll() {
		return new Process( array_merge(
			[
				$this->getPhpExecutable(), $GLOBALS['IP'] .
				'/extensions/TuleapWikiFarm/maintenance/runForAll.php',
			],
			[
				'--script', $this->getFullScriptPath(),
				'--args', implode( ' ', $this->getFormattedArgs() )
			],
			$this->shouldSetMaintenanceMode() ? [ '--set-maintenance' ] : []
		) );
	}

	/**
	 * @return string
	 */
	private function getFullScriptPath() {
		return $GLOBALS['IP'] . '/' . ltrim( $this->getScriptPath(), '/' );
	}

	/**
	 * @return array
	 */
	abstract protected function getFormattedArgs(): array;

	/**
	 * Path to the script file, relative to $IP
	 * @return string
	 */
	abstract protected function getScriptPath(): string;

	/**
	 * @param Process $process
	 */
	protected function modifyProcess( Process $process ) {
		// STUB
	}

	/**
	 * Whether the script should set the maintenance mode on the instance
	 * @return bool
	 */
	protected function shouldSetMaintenanceMode(): bool {
		return false;
	}
}
