<?php

namespace TuleapWikiFarm\ProcessStep;

use Exception;
use MWStake\MediaWiki\Component\ProcessManager\IProcessStep;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use TuleapWikiFarm\InstanceManager;

class InstallInstance implements IProcessStep {
	/** @var InstanceManager */
	private $manager;
	/** @var string */
	private $dbServer;
	/** @var string */
	private $dbUser;
	/** @var string */
	private $dbPass;
	/** @var string */
	private $dbPrefix;
	/** @var int */
	private $projectId;
	/** @var string */
	private $lang;
	/** @var string */
	private $server;
	/** @var string */
	private $adminUser;
	/** @var string */
	private $adminPass;
	/** @var array */
	private $extra;

	/**
	 * @param InstanceManager $manager
	 * @param array $args
	 * @return static
	 * @throws Exception
	 */
	public static function factory( InstanceManager $manager, $args ) {
		$required = [
			'dbserver', 'dbuser', 'dbpass', 'dbprefix',
			'lang', 'server', 'adminuser', 'adminpass', 'project_id'
		];
		foreach ( $required as $key ) {
			if ( !isset( $args[$key] ) ) {
				throw new Exception( "Argument $key must be set" );
			}
		}

		return new static(
			$manager, $args['dbserver'], $args['dbuser'], $args['dbpass'], $args['project_id'],
			$args['dbprefix'], $args['lang'], $args['server'], $args['adminuser'],
			$args['adminpass'], $args['extra'] ?? []
		);
	}

	/**
	 * @param InstanceManager $manager
	 * @param string $dbserver
	 * @param string $dbuser
	 * @param string $dbpass
	 * @param int $projectId
	 * @param string $dbprefix
	 * @param string $lang
	 * @param string $server
	 * @param string $adminuser
	 * @param string $adminpass
	 * @param array $extra
	 */
	public function __construct(
		InstanceManager $manager, $dbserver, $dbuser, $dbpass, $projectId,
		$dbprefix, $lang, $server, $adminuser, $adminpass, $extra = []
	) {
		$this->manager = $manager;
		$this->dbServer = $dbserver;
		$this->dbUser = $dbuser;
		$this->dbPass = $dbpass;
		$this->projectId = $projectId;
		$this->dbPrefix = $dbprefix;
		$this->lang = $lang;
		$this->server = $server;
		$this->adminUser = $adminuser;
		$this->adminPass = $adminpass;
		$this->extra = $extra;
	}

	/**
	 * @param array $data
	 * @return array
	 * @throws Exception
	 */
	public function execute( $data = [] ): array {
		$instance = $this->manager->getStore()->getInstanceById( $data['id'] );
		if ( !$instance ) {
			throw new Exception( 'Failed to install non-registered instance' );
		}

		$scriptPath = $this->manager->generateScriptPath( $instance );
		$dbName = $this->manager->generateDbName( $this->projectId );
		// This cannot be changed
		$this->extra['projectId'] = $this->projectId;

		$phpBinaryFinder = new ExecutableFinder();
		$phpBinaryPath = $phpBinaryFinder->find( 'php' );
		if ( !$phpBinaryPath ) {
			throw new Exception( 'PHP executable not found' );
		}
		// We must run this in isolation, as to not override globals, services...
		$process = new Process( [
			$phpBinaryPath,
			$GLOBALS['IP'] . '/extensions/TuleapWikiFarm/maintenance/installInstance.php',
			'--scriptpath', $scriptPath,
			'--dbname', $dbName,
			'--dbuser', $this->dbUser,
			'--dbpass', $this->dbPass,
			'--dbserver', $this->dbServer,
			'--server', $this->server,
			'--lang', $this->lang,
			'--instanceName', $instance->getName(),
			'--adminuser', $this->adminUser,
			'--adminpass', $this->adminPass,
			'--instanceDir', $this->manager->getDirectoryForInstance( $instance )
		] );

		$err = '';
		$process->run( static function ( $type, $buffer ) use ( &$err ) {
			if ( Process::ERR === $type ) {
				$err .= $buffer;
			}
		} );

		if ( $process->getExitCode() !== 0 ) {
			throw new Exception( $err );
		}

		$instance->setDatabaseName( $dbName );
		$instance->setData( $this->extra );
		$instance->setScriptPath( $scriptPath );
		$this->manager->getStore()->storeEntity( $instance );

		return [ 'id' => $instance->getId() ];
	}
}
