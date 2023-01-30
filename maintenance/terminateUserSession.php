<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Session\SessionManager;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class TerminateUserSession extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'user', 'User to invalidate', false, true, 'u' );
	}

	public function execute() {
		$user = $this->getOption( 'user' );
		if ( $user ) {
			$userFactory = MediaWikiServices::getInstance()->getUserFactory();
			$this->invalidateForUser( $userFactory->newFromName( $user ) );
			return;
		}
		$db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$res = $db->delete(
			'objectcache',
			[ "keyname LIKE '%MWSession%'" ],
			__METHOD__
		);
		if ( $res ) {
			$this->output( "Deleted all sessions\n" );
		} else {
			$this->output( "Failed to delete sessions\n" );
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

$maintClass = TerminateUserSession::class;
require_once RUN_MAINTENANCE_IF_MAIN;
