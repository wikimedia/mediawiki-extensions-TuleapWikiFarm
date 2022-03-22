<?php

namespace TuleapWikiFarm\Rest;

use MediaWiki\Rest\HttpException;
use MWStake\MediaWiki\Component\ProcessManager\ProcessManager;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\ProcessStep\Maintenance\RefreshLinks;
use TuleapWikiFarm\ProcessStep\Maintenance\Update;
use TuleapWikiFarm\ProcessStep\RenameInstance;
use TuleapWikiFarm\StepProcess;
use Wikimedia\ParamValidator\ParamValidator;

class RenameInstanceHandler extends AuthorizedHandler {
	/** @var ProcessManager */
	private $processManager;
	/** @var InstanceManager */
	private $instanceManager;

	/**
	 * @param ProcessManager $processManager
	 * @param InstanceManager $instanceManager
	 */
	public function __construct(
		ProcessManager $processManager, InstanceManager $instanceManager
	) {
		$this->processManager = $processManager;
		$this->instanceManager = $instanceManager;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->assertRights();
		$params = $this->getValidatedParams();
		$source = $params['name'];
		$target = $params['newname'];

		if (
			!$this->instanceManager->checkInstanceNameValidity( $source ) ||
			!$this->instanceManager->getStore()->instanceExists( $source )
		) {
			throw new HttpException( 'Source instance invalid', 422 );
		}
		if ( !$this->instanceManager->isCreatable( $target ) ) {
			throw new HttpException( 'Target instance name invalid, or already exists', 422 );
		}

		$process = new StepProcess( [
			'rename-instance' => [
				'class' => RenameInstance::class,
				'args' => [ $source, $target ],
				'services' => [ 'InstanceManager' ]
			],
			'update' => [
				'class' => Update::class,
				'args' => [ null, '', true ],
				'services' => [ 'InstanceManager' ]
			],
			'refresh-links' => [
				'class' => RefreshLinks::class,
				'args' => [ null, '', true ],
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
	public function getParamSettings() {
		return [
			'name' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'newname' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			]
		];
	}
}
