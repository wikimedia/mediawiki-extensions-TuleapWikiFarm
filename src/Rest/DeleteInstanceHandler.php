<?php

namespace TuleapWikiFarm\Rest;

use Config;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\ProcessStep\DeleteVault;
use TuleapWikiFarm\ProcessStep\DropDatabase;
use TuleapWikiFarm\ProcessStep\UnregisterInstance;
use TuleapWikiFarm\StepProcess;

class DeleteInstanceHandler extends InstanceHandler {
	/** @var Config */
	private $config;

	/**
	 * @param InstanceManager $instanceManager
	 * @param Config $config
	 */
	public function __construct(
		InstanceManager $instanceManager, Config $config
	) {
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
			'main_db' => $this->config->get( 'DBname' ),
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

		return $this->runProcess( $process );
	}
}
