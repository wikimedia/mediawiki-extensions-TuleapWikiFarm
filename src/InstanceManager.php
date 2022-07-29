<?php

namespace TuleapWikiFarm;

use Config;
use DateTime;

class InstanceManager {
	/** @var InstanceStore */
	private $store;
	/** @var Config */
	private $farmConfig;
	/** @var Config */
	private $mainConfig;

	/**
	 * @param InstanceStore $store
	 * @param Config $farmConfig
	 * @param Config $mainConfig
	 */
	public function __construct( InstanceStore $store, Config $farmConfig, Config $mainConfig ) {
		$this->store = $store;
		$this->farmConfig = $farmConfig;
		$this->mainConfig = $mainConfig;
	}

	/**
	 * @return InstanceStore
	 */
	public function getStore(): InstanceStore {
		return $this->store;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function checkInstanceNameValidity( $name ) {
		return !preg_match( '/\/|#|<|>/', $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isCreatable( string $name ) {
		// We check for both direct name and possible script path
		// (Test wiki and Test-wiki would have the same ScriptPath, altough names are different)
		return $this->checkInstanceNameValidity( $name ) &&
			!$this->getStore()->instanceExists( $name, $this->generateScriptPathForName( $name ) );
	}

	/**
	 * @param InstanceEntity $instance
	 * @return string
	 */
	public function generateScriptPath( InstanceEntity $instance ) {
		return $this->generateScriptPathForName( $instance->getName() );
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function generateScriptPathForName( $name ) {
		$name = str_replace( ' ', '-', $name );
		$name = str_replace( '_', '-', $name );

		return "/mediawiki/$name";
	}

	/**
	 * @param string $name
	 * @param int $id
	 * @return InstanceEntity
	 */
	public function getNewInstance( $name, $id ) {
		return new InstanceEntity( $name, $id, new DateTime() );
	}

	/**
	 * @param InstanceEntity $instance
	 * @return false|string
	 */
	public function generateDbName( InstanceEntity $instance ) {
		$centralDbName = $this->getCentralDb();
		if ( $centralDbName ) {
			return $centralDbName;
		}
		return "plugin_mediawiki_{$instance->getId()}";
	}

	/**
	 * @param InstanceEntity $entity
	 * @return string
	 */
	public function generateInstanceDirectoryName( InstanceEntity $entity ) {
		return "/{$entity->getId()}";
	}

	/**
	 * @param InstanceEntity $instance
	 * @param string $path
	 * @return string|null
	 */
	public function getDirectoryForInstance( InstanceEntity $instance, $path = '' ) {
		if ( $instance instanceof RootInstanceEntity ) {
			return $instance->getDirectory();
		}
		$base = $this->getInstanceDirBase() . $instance->getDirectory();
		if ( !$path ) {
			return $base;
		}

		return $base . '/' . $path;
	}

	/**
	 * @return string
	 */
	public function getInstanceDirBase() {
		// Note: Eventhough this is configurable, after initial setup, it cannot be changed!
		return $this->farmConfig->get( 'instanceDir' );
	}

	/**
	 * Name of the central DB
	 *
	 * @return string|null if individual DBs should be used
	 */
	public function getCentralDb(): ?string {
		return $this->farmConfig->get( 'centralDb' );
	}

	/**
	 * @param InstanceEntity $instance
	 * @param string $status
	 * @return bool
	 * @throws \Exception
	 */
	public function setInstanceStatus( InstanceEntity $instance, $status ) {
		$instance->setStatus( $status );
		return $this->store->storeEntity( $instance );
	}

	/**
	 * @param int $projectId
	 * @param string $dbPrefix
	 *
	 * @return bool
	 */
	public function isProjectIdAssigned( $projectId, $dbPrefix ) {
		$idTaken = $this->getStore()->getInstanceById( $projectId ) instanceof InstanceEntity;

		if ( !$idTaken && $this->getCentralDb() !== null ) {
			return $this->getStore()->dbPrefixTaken( $dbPrefix );
		}
		return $idTaken;
	}
}
