<?php

namespace TuleapWikiFarm;

use Config;
use MediaWiki\MediaWikiServices;
use MediaWiki\Session\ImmutableSessionProviderWithCookie;
use MediaWiki\Session\SessionInfo;
use MediaWiki\Session\SessionManager;
use MediaWiki\Session\UserInfo;
use MWGrants;
use WebRequest;

class PreSharedKeySessionProvider extends ImmutableSessionProviderWithCookie {
	/** @var int Tolerate timestamp difference in these many seconds */
	private $acceptableLeeway = 10;
	/** @var string|null */
	private $preSharedKey = null;

	/**
	 * Main constructor. To be called by `ObjectFactory` as specified
	 * in `extension.json/SessionProviders`.
	 * @param Config $mainConfig
	 * @param array $params
	 */
	public function __construct( Config $mainConfig, array $params = [] ) {
		parent::__construct( $params );
		$this->preSharedKey = $mainConfig->get( 'TuleapPreSharedKey' );
	}

	/**
	 * Deprecation prevention for CI
	 * @param SessionManager $manager
	 */
	public function setManagerOverride( SessionManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * @inheritDoc
	 */
	public function provideSessionInfo( WebRequest $request ) {
		// Only relevant for the REST endpoint
		if ( !defined( 'MW_REST_API' ) ) {
			return null;
		}

		$header = $request->getHeader( 'Authorization' );
		if ( strpos( $header, 'Bearer' ) !== 0 ) {
			return null;
		}
		$token = substr( $header, 7 );
		if ( $this->tokenValid( $token ) ) {
			$user = \User::newSystemUser( 'Mediawiki default' );

			$services = MediaWikiServices::getInstance();

			if ( method_exists( $services, 'getGrantsInfo' ) ) {
				// MW 1.38+
				$rights = $services->getGrantsInfo()->getGrantRights( [ 'farm-management' ] );
			} else {
				$rights = MWGrants::getGrantRights( [ 'farm-management' ] );
			}

			return new SessionInfo( SessionInfo::MAX_PRIORITY, [
				'provider' => $this,
				'id' => null,
				'userInfo' => UserInfo::newFromUser( $user, true ),
				'persisted' => false,
				'forceUse' => true,
				'metadata' => [
					'app' => 'tuleap',
					'rights' => $rights,
				],
			] );
		}

		return null;
	}

	/**
	 * @param string $token Token provided via Authentication header
	 * @return bool
	 */
	private function tokenValid( $token ): bool {
		if ( !$this->preSharedKey ) {
			$this->logger->error( 'wgTuleapPreSharedKey is not set' );
			return false;
		}

		return $this->matchAny( $token, $this->getAcceptableTokens( time(), $this->preSharedKey ) );
	}

	/**
	 * @param int $timestamp Current timestamp
	 * @param string $secret Pre-shared key
	 * @return array
	 */
	private function getAcceptableTokens( int $timestamp, $secret ): array {
		$allowedTimestamps = range(
			$timestamp - $this->acceptableLeeway,
			$timestamp + $this->acceptableLeeway + 1
		);

		return array_map( static function ( $ts ) use ( $secret ) {
			return hash_hmac( 'sha256', $secret, $ts );
		}, $allowedTimestamps );
	}

	/**
	 * @param string $token
	 * @param array $valid Array of tolerated tokens
	 * @return bool
	 */
	private function matchAny( $token, $valid ) {
		foreach ( $valid as $potential ) {
			if ( hash_equals( $token, $potential ) ) {
				return true;
			}
		}

		return false;
	}
}
