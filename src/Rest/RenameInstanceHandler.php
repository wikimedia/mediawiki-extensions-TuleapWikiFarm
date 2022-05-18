<?php

namespace TuleapWikiFarm\Rest;

use MediaWiki\Rest\HttpException;
use TuleapWikiFarm\InstanceManager;
use Wikimedia\ParamValidator\ParamValidator;

class RenameInstanceHandler extends AuthorizedHandler {
	/** @var InstanceManager */
	private $instanceManager;

	/**
	 * @param InstanceManager $instanceManager
	 */
	public function __construct(
		InstanceManager $instanceManager
	) {
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
			$this->instanceManager->isCreatable( $source )
		) {
			throw new HttpException( 'Source instance invalid', 422 );
		}
		if ( !$this->instanceManager->isCreatable( $target ) ) {
			throw new HttpException( 'Target instance name invalid, or already exists', 422 );
		}

		$entity = $this->instanceManager->getStore()->getInstanceByName( $source );
		$entity->setName( $target );
		$entity->setScriptPath( $this->instanceManager->generateScriptPath( $entity ) );

		$success = $this->instanceManager->getStore()->storeEntity( $entity );

		return $this->getResponseFactory()->createJson( [ 'status' => $success ] );
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
