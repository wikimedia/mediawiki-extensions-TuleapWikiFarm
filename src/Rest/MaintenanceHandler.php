<?php

namespace TuleapWikiFarm\Rest;

use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use TuleapWikiFarm\InstanceEntity;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\StepProcess;
use Wikimedia\ParamValidator\ParamValidator;

class MaintenanceHandler extends AuthorizedHandler {
	/** @var InstanceManager */
	protected $instanceManager;

	private $scriptMap;

	/**
	 * @param InstanceManager $instanceManager
	 */
	public function __construct(
		InstanceManager $instanceManager
	) {
		$this->instanceManager = $instanceManager;
		$this->scriptMap = \ExtensionRegistry::getInstance()->getAttribute(
			'TuleapWikiFarmMaintenanceScripts'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->assertRights();
		$params = $this->getValidatedParams();
		$script = $params['script'];
		if ( !isset( $this->scriptMap[$script] ) ) {
			throw new HttpException( "Unknown script: $script" );
		}
		$timeout = $params['timeout'];

		$spec = $this->scriptMap[$script];
		$spec['args'] = $spec['args'] ?? [];

		$instanceName = $params['instance'];

		if ( $instanceName === '*' ) {
			array_unshift( $spec['args'], -1 );
			if ( $timeout < 3600 ) {
				// Make sure enough time is given to big processes
				$timeout = 3600;
			}
		} else {
			if ( !$this->instanceManager->checkInstanceNameValidity( $instanceName ) ) {
				throw new HttpException( 'Invalid instance name: ' . $instanceName, 422 );
			}
			$instance = $this->instanceManager->getStore()->getInstanceByName( $instanceName );
			if (
				!$instance ||
				!in_array( $instance->getStatus(), [
					InstanceEntity::STATE_MIGRATION, InstanceEntity::STATE_READY
				] )
			) {
				throw new HttpException( 'Instance not available or not ready', 400 );
			}
			array_unshift( $spec['args'], $instance->getId() );
		}

		$body = $this->getValidatedBody();
		$spec['args'][] = $body;

		$process = new StepProcess( [
			$script => $spec,
		] );

		$response = [];
		try {
			$data = $process->process( $timeout );
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
			return new JsonBodyValidator( [] );
		}

		throw new HttpException( 'Content-Type header must be application/json' );
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'instance' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'script' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'timeout' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_DEFAULT => 300
			],
		];
	}
}
