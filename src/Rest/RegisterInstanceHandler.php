<?php

namespace TuleapWikiFarm\Rest;

use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use TuleapWikiFarm\InstanceEntity;
use TuleapWikiFarm\InstanceManager;
use Wikimedia\ParamValidator\ParamValidator;

class RegisterInstanceHandler extends AuthorizedHandler {
	/** @var InstanceManager */
	protected $instanceManager;

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
		$name = $params['name'];
		$body = $this->getValidatedBody();

		if ( !$this->instanceManager->isCreatable( $name ) ) {
			throw new HttpException( 'Instance with this name already exists' );
		}

		$dbPrefix = $body['dbprefix'];
		if ( $this->instanceManager->getCentralDb() !== null && !$dbPrefix ) {
			throw new HttpException(
				'When configured to use central database, param dbprefix must be set'
			);
		}
		$projectId = $body['project_id'];
		if ( $this->instanceManager->isProjectIdAssigned( $projectId, $dbPrefix ) ) {
			throw new HttpException( 'Instance for this groupid or dbprefix already exists' );
		}

		$entity = $this->instanceManager->getNewInstance( $name, $projectId );
		if ( $dbPrefix ) {
			$entity->setDatabasePrefix( $dbPrefix );
		}
		$entity->setDirectory( $this->instanceManager->generateInstanceDirectoryName( $entity ) );
		$entity->setScriptPath( $this->instanceManager->generateScriptPath( $entity ) );
		$entity->setDatabaseName( $this->instanceManager->generateDbName( $entity ) );
		$entity->setStatus( InstanceEntity::STATE_MIGRATION );
		if ( !$this->instanceManager->getStore()->storeEntity( $entity ) ) {
			throw new HttpException( "Could not register instance" );
		}
		return $this->getResponseFactory()->createJson( $entity->jsonSerialize() );
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ) {
		if ( $contentType === 'application/json' ) {
			return new JsonBodyValidator( [
				'project_id' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => true,
					ParamValidator::PARAM_TYPE => 'integer'
				],
				'project_name' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => true,
					ParamValidator::PARAM_TYPE => 'string'
				],
				'dbprefix' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_DEFAULT => ''
				]
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
