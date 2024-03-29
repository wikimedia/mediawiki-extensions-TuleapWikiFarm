<?php

namespace TuleapWikiFarm\ProcessStep;

use Exception;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\IProcessStep;

class UnregisterInstance implements IProcessStep {
	/** @var InstanceManager */
	private $manager;
	/** @var int */
	private $id;

	/**
	 * @param InstanceManager $manager
	 * @param int $id InstanceID
	 */
	public function __construct( InstanceManager $manager, $id ) {
		$this->manager = $manager;
		$this->id = $id;
	}

	/**
	 * @param array $data
	 * @return array
	 * @throws Exception
	 */
	public function execute( $data = [] ): array {
		if ( !$this->manager->getStore()->deleteInstance( $this->id ) ) {
			throw new Exception( 'Failed to delete instance entry' );
		}

		return $data;
	}
}
