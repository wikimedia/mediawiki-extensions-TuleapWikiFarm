<?php

namespace TuleapWikiFarm\Rest;

use Config;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use MWStake\MediaWiki\Component\ProcessManager\ProcessManager;
use TuleapWikiFarm\InstanceEntity;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\ProcessStep\CreateInstanceVault;
use TuleapWikiFarm\ProcessStep\InstallInstance;
use TuleapWikiFarm\ProcessStep\RegisterInstance;
use TuleapWikiFarm\ProcessStep\SetInstanceStatus;
use TuleapWikiFarm\StepProcess;
use Wikimedia\ParamValidator\ParamValidator;

class CreateInstanceHandler extends AuthorizedHandler {
	/** @var ProcessManager */
	private $processManager;
	/** @var InstanceManager */
	private $instanceManager;
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
		$this->instanceManager = $instanceManager;
		$this->config = $config;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->assertRights();
		$params = $this->getValidatedParams();
		if ( !$this->instanceManager->isCreatable( $params['name'] ) ) {
			throw new HttpException( 'Instance name not valid or instance exists', 422 );
		}

		$body = $this->getValidatedBody();
		$body['dbprefix'] = $this->config->get( 'DBprefix' );
		$body['server'] = $this->config->get( 'Server' );

		$process = new StepProcess( [
			'register-instance' => [
				'class' => RegisterInstance::class,
				'args' => [ $params['name'] ],
				'services' => [ 'InstanceManager' ]
			],
			'create-vault' => [
				'class' => CreateInstanceVault::class,
				'args' => [],
				'services' => [ 'InstanceManager' ]
			],
			'install-instance' => [
				'factory' => InstallInstance::class . '::factory',
				'args' => [ $body ],
				'services' => [ 'InstanceManager' ]
			],
			'set-instance-status' => [
				'class' => SetInstanceStatus::class,
				'args' => [ InstanceEntity::STATE_READY ],
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

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ) {
		if ( $contentType === 'application/json' ) {
			return new JsonBodyValidator( [
				'lang' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_DEFAULT => $this->config->get( 'LanguageCode' ),
				],
				'dbserver' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_DEFAULT => $this->config->get( 'DBserver' ),
				],
				'dbuser' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_DEFAULT => $this->config->get( 'DBuser' ),
				],
				'dbpass' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_DEFAULT => $this->config->get( 'DBpassword' ),
				],
				'adminuser' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_DEFAULT => 'WikiSysop'
				],
				'adminpass' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => true,
					ParamValidator::PARAM_TYPE => 'string',
				],
				'project_id' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => true,
					ParamValidator::PARAM_TYPE => 'integer',
				],
			] );
		}
		return parent::getBodyValidator( $contentType );
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return [
			'name' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			]
		];
	}
}
