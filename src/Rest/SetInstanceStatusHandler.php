<?php

namespace TuleapWikiFarm\Rest;

use MediaWiki\Rest\HttpException;
use TuleapWikiFarm\InstanceManager;

class SetInstanceStatusHandler extends InstanceHandler {
	/** @var string */
	private $status;
	/** @var string */
	private $requiredState;

	/**
	 * @param InstanceManager $manager
	 * @param string $status
	 * @param string $requiredState
	 */
	public function __construct( InstanceManager $manager, $status, $requiredState ) {
		parent::__construct( $manager );
		$this->status = $status;
		$this->requiredState = $requiredState;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->assertRights();
		$instance = $this->getInstance();
		if ( $instance->getStatus() !== $this->requiredState ) {
			throw new HttpException( 'Instance must be in state ' . $this->requiredState );
		}

		$res = $this->getManager()->setInstanceStatus( $instance, $this->status );

		return $this->returnJson( [
			'success' => $res,
		], $res ? 200 : 500 );
	}
}
