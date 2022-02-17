<?php

namespace TuleapWikiFarm\Rest;

use MediaWiki\Rest\HttpException;
use TuleapWikiFarm\InstanceEntity;
use TuleapWikiFarm\InstanceManager;
use Wikimedia\ParamValidator\ParamValidator;

abstract class InstanceHandler extends AuthorizedHandler {
	/** @var InstanceManager */
	private $manager;

	/**
	 * @param InstanceManager $manager
	 */
	public function __construct( InstanceManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * @return InstanceEntity
	 * @throws HttpException
	 */
	protected function getInstance(): InstanceEntity {
		$params = $this->getValidatedParams();
		if ( !$this->manager->checkInstanceNameValidity( $params['name'] ) ) {
			throw new HttpException( 'Instance name is not valid', 422 );
		}

		$store = $this->manager->getStore();
		$entity = $store->getInstanceByName( $params['name'] );
		if ( !$entity ) {
			throw new HttpException( 'Instance ' . $params['name'] . ' does not exist', 404 );
		}

		return $entity;
	}

	/**
	 * @return InstanceManager
	 */
	protected function getManager(): InstanceManager {
		return $this->manager;
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
			]
		];
	}
}
