<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Session\SessionManager;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class TerminateAllSessions extends Maintenance {
	private $batchSize = 1000;

	public function __construct() {
		parent::__construct();
		$this->addOption( 'user', 'User to invalidate', false, true, 'u' );
	}

	public function execute() {
		$user = $this->getOption( 'user' );
		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		if ( $user ) {
			$this->invalidateForUser( $userFactory->newFromName( $user ) );
			return;
		}

		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$users = $lbFactory->getMainLB()->getConnection( DB_REPLICA )->select(
			'user',
			[ 'user_name' ],
			[],
			__METHOD__
		);

		$i = 0;
		foreach ( $users as $userRow ) {
			$i++;
			$this->invalidateForUser( $userFactory->newFromName( $userRow->user_name ) );

			if ( $i % $this->batchSize ) {
				$lbFactory->waitForReplication();
			}
		}
	}

	/**
	 * @param User|bool $user
	 */
	private function invalidateForUser( $user ) {
		if ( !( $user instanceof User ) ) {
			$this->fatalError( "User $user is invalid\n" );
		}
		$sessionManager = SessionManager::singleton();
		try {
			$sessionManager->invalidateSessionsForUser( $user );
			if ( $user->getId() ) {
				$this->output( 'Invalidated session for user ' . $user->getName() . "\n" );
			} else {
				$this->output( "Cannot find user {$user->getName()}, tried to invalidate anyways\n" );
			}
		} catch ( Exception $ex ) {
			$this->output( "Failed to invalidate sessions for user {$user->getName()} | "
				. str_replace( [ "\r", "\n" ], ' ', $ex->getMessage() ) . "\n" );
		}
	}

}

$maintClass = TerminateAllSessions::class;
require_once RUN_MAINTENANCE_IF_MAIN;
