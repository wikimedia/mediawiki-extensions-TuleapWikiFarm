<?php

namespace TuleapWikiFarm\ProcessStep;

use Exception;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\IProcessStep;

class RegisterInstance implements IProcessStep {
	/** @var InstanceManager */
	private $manager;
	/** @var string */
	private $name;
	/** @var int */
	private $projectId;

	/**
	 * @param InstanceManager $manager
	 * @param string $name
	 * @param int $projectId
	 */
	public function __construct( InstanceManager $manager, $name, $projectId ) {
		$this->manager = $manager;
		$this->name = $name;
		$this->projectId = $projectId;
	}

	/**
	 * @param array $data
	 * @return array
	 * @throws Exception
	 */
	public function execute( $data = [] ): array {
		$entity = $this->manager->getNewInstance( $this->name, $this->projectId );

		if ( !$this->manager->getStore()->storeEntity( $entity ) ) {
			throw new Exception( "Could not register instance" );
		}

		return [ 'id' => $entity->getId() ];
	}
}
