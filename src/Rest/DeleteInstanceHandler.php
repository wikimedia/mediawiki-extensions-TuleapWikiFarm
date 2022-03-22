<?php

namespace TuleapWikiFarm\Rest;

use Config;
use MWStake\MediaWiki\Component\ProcessManager\ProcessManager;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\ProcessStep\DeleteVault;
use TuleapWikiFarm\ProcessStep\DropDatabase;
use TuleapWikiFarm\ProcessStep\UnregisterInstance;
use TuleapWikiFarm\StepProcess;

class DeleteInstanceHandler extends InstanceHandler {
	/** @var ProcessManager */
	private $processManager;
	/** @var Config */
	private $config;

	/**
	 * @param ProcessManager $processManager
	 * @param InstanceManager $instanceManager
	 * @param Config $config
	 */
	public function __construct(
		ProcessManager $processManager, InstanceManager $instanceManager, Config $config
	) {
		$this->processManager = $processManager;
		$this->config = $config;
		parent::__construct( $instanceManager );
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->assertRights();
		$instance = $this->getInstance();

		$dbConnection = [
			'type' => $this->config->get( 'DBtype' ),
			'host' => $this->config->get( 'DBserver' ),
			'user' => $this->config->get( 'DBuser' ),
			'password' => $this->config->get( 'DBpassword' ),
		];
		$process = new StepProcess( [
			'delete-vault' => [
				'class' => DeleteVault::class,
				'args' => [ $instance->getId() ],
				'services' => [ 'InstanceManager' ]
			],
			'drop-database' => [
				'class' => DropDatabase::class,
				'args' => [ $instance->getId(), $dbConnection ],
				'services' => [ 'InstanceManager' ]
			],
			'unregister-instance' => [
				'class' => UnregisterInstance::class,
				'args' => [ $instance->getId() ],
				'services' => [ 'InstanceManager' ]
			]
		] );

		$response = [];
		try {
			$data = $process->process();
			$response['status'] = 'success';
			$response['output'] = $data;
		} catch ( \Exception $ex ) {
			$response['status'] = 'error';
			$response['error'] = [
				'code' => $ex->getCode(),
				'message' => $ex->getMessage(),
				'trace' => $ex->getTraceAsString(),
			];
		}
		return $this->getResponseFactory()->createJson( $response );
	}
}
