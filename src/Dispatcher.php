<?php

namespace TuleapWikiFarm;

use Maintenance;

class Dispatcher {

	/**
	 * @var array
	 */
	private $server = [];

	/**
	 * @var array
	 */
	private $request = [];

	/**
	 * @var array
	 */
	private $globals = [];

	/**
	 * @var InstanceManager
	 */
	private $manager;

	/** @var GlobalStorage */
	private $globalStorage;

	/**
	 *
	 * @var InstanceEntity|null
	 */
	private $instance = null;

	/**
	 *
	 * @var string
	 */
	private $instanceVaultPathname = '';

	/**
	 * @param array $server $_SERVER
	 * @param array $request $_REQUEST
	 * @param array &$globals $GLOBALS
	 * @param InstanceManager $manager
	 * @param GlobalStorage $globalStorage
	 */
	public function __construct(
		$server, $request, &$globals,
		InstanceManager $manager, GlobalStorage $globalStorage
	) {
		$this->server = $server;
		$this->request = $request;
		$this->globals =& $globals;
		$this->manager = $manager;
		$this->globalStorage = $globalStorage;
	}

	/**
	 *
	 * @var string[]
	 */
	private $filesToRequire = [];

	/**
	 * @return string[]
	 */
	public function getFilesToRequire() {
		$this->initInstance();
		$this->defineConstants();
		$this->includeTuleapFile();
		if ( $this->isCliInstallerContext() ) {
			return $this->filesToRequire;
		}

		if ( $this->isInstanceWikiCall() ) {
			$this->initInstanceVaultPathname();

			$this->redirectIfNoInstance();
			$this->redirectIfNotReady();
			$this->setupEnvironment();
		} else {
			$this->setupEnvironment( false );
		}

		return $this->filesToRequire;
	}

	private function initInstance() {
		if ( $this->isMaintenance() ) {
			// this works for all maintenance scripts.
			// put an --sfr "WIKI_PATH_NAME" on the call and the settings
			// files of the right wiki will be included.
			//TODO: Inject like $_REQUEST
			$extractor = new CliArgInstanceNameExtractor();
			$name = $extractor->extractInstanceName( $this->globals['argv'] );
			if ( empty( $name ) ) {
				$name = 'w';
			}
			$idType = 'name';
			if ( is_numeric( $name ) && is_int( $name + 0 ) ) {
				$id = (int)$name;
				$this->instance = $this->manager->getStore()->getInstanceById( $id );
				$idType = 'project_id';
			} else {
				$this->instance = $this->manager->getStore()->getInstanceByName( $name );
			}

			if ( !$this->instance ) {
				echo "Invalid instance $idType: $name";
				die();
			}
			// We need to reset let the maintenance script reload the arguments, as we now have
			// removed the "--sfr" flag, which would lead to an "Unexpected option" error
			/** @var Maintenance */
			$this->globals['maintenance']->clearParamsAndArgs();
			$this->globals['maintenance']->loadParamsAndArgs();
		} else {
			$this->trySetFromOauthCall();
			$name = isset( $this->request['sfr'] ) ? $this->request['sfr'] : 'w';
			$this->instance = $this->manager->getStore()->getInstanceByName( $name );
			unset( $this->request['sfr'] );

			$this->redirectIfNoInstance();
		}
	}

	private function trySetFromOauthCall() {
		if (
			isset( $this->request['state'] ) &&
			isset( $this->request['sfr'] ) && $this->request['sfr'] === '_oauth'
		) {
			$instanceName = $this->globalStorage->getAuthInstanceFromState( $this->request['state'] );
			if ( $instanceName ) {
				$this->request['sfr'] = $instanceName;
				$this->globalStorage->destroyAuthRecordForInstance( $instanceName );
			}
		}
	}

	private function initInstanceVaultPathname() {
		$this->instanceVaultPathname = $this->manager->getDirectoryForInstance( $this->instance );
	}

	private function defineConstants() {
		// For "root"-wiki calls only
		if ( $this->isRootWikiCall() ) {
			define( 'FARMER_IS_ROOT_WIKI_CALL', true );
			define( 'FARMER_CALLED_INSTANCE', '' );
		} else {
			define( 'FARMER_IS_ROOT_WIKI_CALL', false );
			define( 'FARMER_CALLED_INSTANCE', $this->instance->getName() );
		}
	}

	/**
	 * @return bool
	 */
	private function isRootWikiCall() {
		return !$this->instance || $this->instance instanceof RootInstanceEntity;
	}

	/**
	 * @return bool
	 */
	private function isInstanceWikiCall() {
		return $this->instance &&
			$this->instance instanceof InstanceEntity &&
			!( $this->instance instanceof RootInstanceEntity );
	}

	/**
	 * @param bool $forInstance
	 */
	private function setupEnvironment( $forInstance = true ) {
		$this->globals['wgScriptPath'] = $this->instance->getScriptPath();
		$this->globals['wgResourceBasePath'] = $this->instance->getScriptPath();
		$this->globals['wgArticlePath'] = "{$this->instance->getScriptPath()}/$1";
		$this->globals['wgUploadPath'] = "{$this->instance->getScriptPath()}/img_auth.php";
		$this->globals['wgReadOnlyFile'] = "{$this->globals['wgUploadDirectory']}/lock_yBgMBwiR";
		$this->globals['wgFileCacheDirectory'] = "{$this->globals['wgUploadDirectory']}/cache";
		$this->globals['wgDeletedDirectory'] = "{$this->globals['wgUploadDirectory']}/deleted";

		if ( $forInstance ) {
			$this->globals['wgCacheDirectory'] = "{$this->instanceVaultPathname}/cache";
			$this->globals['wgSitename'] = $this->instance->getName();
			$this->globals['wgUploadDirectory'] = "{$this->instanceVaultPathname}/images";
			$this->globals['wgTuleapProjectId'] = $this->instance->getId();
			$this->globals['wgDBname'] = $this->instance->getDatabaseName();
			$this->globals['wgDBprefix'] = $this->instance->getDatabasePrefix();
			$this->globals['wgTuleapData'] = $this->instance->getData();
			define( 'WIKI_FARMING', true );
		}
	}

	private function redirectIfNotReady() {
		if ( $this->isMaintenance() ) {
			return;
		}

		if ( $this->instance->getStatus() === InstanceEntity::STATE_SUSPENDED ) {
			$this->doInclude( $this->globals['IP'] . '/LocalSettings.SUSPENDED.php' );
			return;
		}
		if ( $this->instance->getStatus() === InstanceEntity::STATE_READY ) {
			return;
		}
		echo "Instance not available. Status: " . $this->instance->getStatus();
		die();
	}

	private function redirectIfNoInstance() {
		if ( $this->instance === null ) {
			echo "No such instance";
			die();
		}
	}

	private function includeTuleapFile() {
		$this->doInclude( $this->globals['IP'] . '/LocalSettings.Tuleap.php' );
	}

	/**
	 * @param string $pathname
	 */
	private function doInclude( $pathname ) {
		$this->filesToRequire[] = $pathname;
	}

	private function isCliInstallerContext() {
		return defined( 'MEDIAWIKI_INSTALL' );
	}

	/**
	 * @return bool
	 */
	private function isMaintenance() {
		return defined( 'DO_MAINTENANCE' ) && is_file( RUN_MAINTENANCE_IF_MAIN );
	}
}
