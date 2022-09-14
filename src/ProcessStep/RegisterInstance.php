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
	/** @var string */
	private $dbPrefix;
	/** @var null */
	private $lang;

	/**
	 * @param InstanceManager $manager
	 * @param string $name
	 * @param int $projectId
	 * @param string $dbPrefix
	 * @param null $lang
	 */
	public function __construct( InstanceManager $manager, $name, $projectId, $dbPrefix, $lang = null ) {
		$this->manager = $manager;
		$this->name = $name;
		$this->projectId = $projectId;
		$this->dbPrefix = $dbPrefix;
		$this->lang = $lang;
	}

	/**
	 * @param array $data
	 * @return array
	 * @throws Exception
	 */
	public function execute( $data = [] ): array {
		$entity = $this->manager->getNewInstance( $this->name, $this->projectId );
		if ( $this->dbPrefix ) {
			$entity->setDatabasePrefix( $this->dbPrefix );
		}
		if ( $this->lang !== null ) {
			$entity->setDataItem( 'lang', $this->lang );
		}

		if ( !$this->manager->getStore()->storeEntity( $entity ) ) {
			throw new Exception( "Could not register instance" );
		}

		return [ 'id' => $entity->getId() ];
	}
}
