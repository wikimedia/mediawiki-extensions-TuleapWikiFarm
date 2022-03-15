<?php

namespace TuleapWikiFarm;

use Config;
use DateTime;

class InstanceManager {
	/** @var InstanceStore */
	private $store;
	/** @var Config */
	private $farmConfig;

	/**
	 * @param InstanceStore $store
	 * @param Config $farmConfig
	 */
	public function __construct( InstanceStore $store, Config $farmConfig ) {
		$this->store = $store;
		$this->farmConfig = $farmConfig;
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
		return $this->checkInstanceNameValidity( $name ) &&
			!$this->getStore()->instanceExists( $name );
	}

	/**
	 * @param InstanceEntity $instance
	 * @return string
	 */
	public function generateScriptPath( InstanceEntity $instance ) {
		$name = $instance->getName();
		$name = str_replace( ' ', '-', $name );
		$name = str_replace( '_', '-', $name );

		return "/mediawiki/$name";
	}

	/**
	 * @param string $name
	 * @return InstanceEntity
	 */
	public function getNewInstance( $name ) {
		return new InstanceEntity( $name, new DateTime() );
	}

	/**
	 * @param InstanceEntity $entity
	 * @param string $newName
	 * @return InstanceEntity
	 */
	public function getRenamedInstanceEntity( InstanceEntity $entity, $newName ) {
		$newEntity = new InstanceEntity(
			$newName,
			$entity->getCreatedAt(),
			$entity->getId(),
			null,
			$entity->getDatabaseName(),
			null,
			$entity->getStatus(),
			$entity->getData()
		);

		$newEntity->setDirectory( $this->generateInstanceDirectoryName( $newEntity ) );
		$newEntity->setScriptPath( $this->generateScriptPath( $newEntity ) );

		return $newEntity;
	}

	/**
	 * @param int $projectId
	 * @return false|string
	 */
	public function generateDbName( $projectId ) {
		return "mediawiki_$projectId";
	}

	/**
	 * @param InstanceEntity $entity
	 * @return string
	 */
	public function generateInstanceDirectoryName( InstanceEntity $entity ) {
		$dirName = str_replace( ' ', '_', $entity->getName() );
		return "/{$dirName}";
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
	 * @param InstanceEntity $entity
	 * @param string $var
	 * @param string $old
	 * @param string $new
	 * @return bool
	 */
	public function replaceConfigVar( InstanceEntity $entity, $var, $old, $new ): bool {
		$filePath = $this->getDirectoryForInstance( $entity, 'LocalSettings.php' );
		if ( !file_exists( $filePath ) ) {
			return false;
		}
		$content = file_get_contents( $filePath );
		$re = '/((\$GLOBALS\[\'' . $var . '\'\]|\$' . $var . ') = [\"\'])(.*?)([\"\'])/m';
		$content = preg_replace( $re, "$1{$new}$4", $content );

		if ( $content === null ) {
			throw new \Exception( preg_last_error_msg() );
		}
		return file_put_contents( $filePath, $content );
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
}
