<?php

namespace TuleapWikiFarm\ProcessStep;

use Exception;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\IProcessStep;

class SetInstanceStatus implements IProcessStep {
	/** @var InstanceManager */
	private $manager;
	/** @var string */
	private $status;

	/**
	 * @param InstanceManager $manager
	 * @param string $status
	 */
	public function __construct( InstanceManager $manager, $status ) {
		$this->manager = $manager;
		$this->status = $status;
	}

	/**
	 * @param array $data
	 * @return array
	 * @throws Exception
	 */
	public function execute( $data = [] ): array {
		$entity = $this->manager->getStore()->getInstanceById( $data['id'] );

		if ( !$entity ) {
			throw new Exception( "Cannot change state of non-existing instance" );
		}

		$entity->setStatus( $this->status );

		$this->manager->getStore()->storeEntity( $entity );

		return $data;
	}
}
