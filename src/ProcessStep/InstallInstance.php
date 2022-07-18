<?php

namespace TuleapWikiFarm\ProcessStep;

use Config;
use Exception;
use Symfony\Component\Process\Process;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\IProcessStep;

class InstallInstance implements IProcessStep {
	/** @var InstanceManager */
	private $manager;
	/** @var Config */
	private $config;
	/** @var string */
	private $dbServer;
	/** @var string */
	private $dbUser;
	/** @var string */
	private $dbPass;
	/** @var string */
	private $dbPrefix;
	/** @var string */
	private $lang;
	/** @var string */
	private $server;
	/** @var array */
	private $extra;

	/**
	 * @param InstanceManager $manager
	 * @param Config $config
	 * @param array $args
	 * @return static
	 * @throws Exception
	 */
	public static function factory( InstanceManager $manager, Config $config, $args ) {
		$required = [
			'dbserver', 'dbuser', 'dbpass', 'dbprefix', 'lang', 'server'
		];
		foreach ( $required as $key ) {
			if ( !isset( $args[$key] ) ) {
				throw new Exception( "Argument $key must be set" );
			}
		}

		return new static(
			$manager, $config, $args['dbserver'], $args['dbuser'], $args['dbpass'],
			$args['dbprefix'], $args['lang'], $args['server'], $args['extra'] ?? []
		);
	}

	/**
	 * @param InstanceManager $manager
	 * @param Config $config
	 * @param string $dbserver
	 * @param string $dbuser
	 * @param string $dbpass
	 * @param string $dbprefix
	 * @param string $lang
	 * @param string $server
	 * @param array $extra
	 */
	public function __construct(
		InstanceManager $manager, Config $config, $dbserver, $dbuser, $dbpass,
		$dbprefix, $lang, $server, $extra = []
	) {
		$this->manager = $manager;
		$this->config = $config;
		$this->dbServer = $dbserver;
		$this->dbUser = $dbuser;
		$this->dbPass = $dbpass;
		$this->dbPrefix = $dbprefix;
		$this->lang = $lang;
		$this->server = $server;
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
		$dbName = $this->manager->generateDbName( $instance );
		$adminPass = bin2hex( random_bytes( 16 ) );

		// We must run this in isolation, as to not override globals, services...
		$process = new Process( [
			$this->config->get( 'PhpCli' ),
			$GLOBALS['IP'] . '/extensions/TuleapWikiFarm/maintenance/installInstance.php',
			'--scriptpath', $scriptPath,
			'--dbname', $dbName,
			'--dbprefix', $this->dbPrefix,
			'--dbuser', $this->dbUser,
			'--dbpass', $this->dbPass,
			'--dbserver', $this->dbServer,
			'--server', $this->server,
			'--lang', $this->lang,
			'--instanceName', $instance->getName(),
			'--adminuser', 'WikiSysop',
			'--adminpass', $adminPass,
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
		$instance->setScriptPath( $scriptPath );
		$this->manager->getStore()->storeEntity( $instance );

		return [
			'id' => $instance->getId(),
			'admin_user' => 'WikiSysop',
			'admin_pass' => $adminPass
		];
	}
}
