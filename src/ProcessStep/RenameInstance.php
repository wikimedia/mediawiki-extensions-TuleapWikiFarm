<?php

namespace TuleapWikiFarm\ProcessStep;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\IProcessStep;

class RenameInstance implements IProcessStep {
	/** @var InstanceManager */
	private $manager;
	/** @var string */
	private $name;
	/** @var string */
	private $newName;

	/**
	 * @param InstanceManager $manager
	 * @param string $name
	 * @param string $newName
	 */
	public function __construct( InstanceManager $manager, $name, $newName ) {
		$this->manager = $manager;
		$this->name = $name;
		$this->newName = $newName;
	}

	/**
	 * @param array $data
	 * @return array
	 * @throws Exception
	 */
	public function execute( $data = [] ): array {
		$entity = $this->manager->getStore()->getInstanceByName( $this->name );
		$newEntity = $this->manager->getRenamedInstanceEntity( $entity, $this->newName );

		$fs = new Filesystem();
		$fs->rename(
			$this->manager->getDirectoryForInstance( $entity ),
			$this->manager->getDirectoryForInstance( $newEntity )
		);

		if ( !$fs->exists( $this->manager->getDirectoryForInstance( $newEntity ) ) ) {
			throw new Exception( "Could not move instance vault!" );
		}

		$warnings = [];
		try {
			$this->manager->replaceConfigVar(
				$newEntity, 'wgScriptPath', $entity->getScriptPath(), $newEntity->getScriptPath()
			);
			$this->manager->replaceConfigVar(
				$newEntity, 'wgSitename', $entity->getName(), $newEntity->getName()
			);
		} catch ( Exception $ex ) {
			$warnings[] = 'Failed to replace LocalSettings values: ' . $ex->getMessage();
		}

		$this->manager->getStore()->storeEntity( $newEntity );

		return [ 'id' => $newEntity->getId(), 'warnings' => $warnings ];
	}
}
