<?php

namespace TuleapWikiFarm\Rest;

use Config;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use TuleapWikiFarm\InstanceEntity;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\ProcessStep\CreateInstanceVault;
use TuleapWikiFarm\ProcessStep\InstallInstance;
use TuleapWikiFarm\ProcessStep\RegisterInstance;
use TuleapWikiFarm\ProcessStep\SetInstanceStatus;
use TuleapWikiFarm\StepProcess;
use Wikimedia\ParamValidator\ParamValidator;

class CreateInstanceHandler extends AuthorizedHandler {
	/** @var InstanceManager */
	private $instanceManager;
	/** @var Config */
	private $config;

	/**
	 * @param InstanceManager $instanceManager
	 * @param Config $config
	 */
	public function __construct(
		InstanceManager $instanceManager, Config $config
	) {
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
		$body['server'] = $this->config->get( 'Server' );
		// `$body['dbprefix']` must be set for `install-instance` to work.
		$body['dbprefix'] = $body['dbprefix'] ?? '';
		$dbPrefix = $body['dbprefix'];
		if ( $this->instanceManager->getCentralDb() !== null && !$dbPrefix ) {
			throw new HttpException(
				'When configured to use central DB, param dbprefix must be set'
			);
		}

		if ( $this->instanceManager->isProjectIdAssigned( $body['project_id'], $dbPrefix ) ) {
			throw new HttpException( 'Instance for this project already exists', 422 );
		}

		$process = new StepProcess( [
			'register-instance' => [
				'class' => RegisterInstance::class,
				'args' => [ $params['name'], $body['project_id'], $dbPrefix ],
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
				'services' => [ 'InstanceManager', 'MainConfig' ]
			],
			'set-instance-status' => [
				'class' => SetInstanceStatus::class,
				'args' => [ InstanceEntity::STATE_READY ],
				'services' => [ 'InstanceManager' ]
			],
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
				'project_id' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => true,
					ParamValidator::PARAM_TYPE => 'integer',
				],
			] );
		}
		throw new HttpException( 'Content-Type header must be application/json' );
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
