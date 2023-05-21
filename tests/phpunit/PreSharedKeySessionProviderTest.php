<?php

namespace TuleapWikiFarm\Tests;

use HashConfig;
use MediaWiki\Session\SessionInfo;
use MediaWiki\Session\SessionManager;
use PHPUnit\Framework\TestCase;
use TuleapWikiFarm\PreSharedKeySessionProvider;

class PreSharedKeySessionProviderTest extends TestCase {
	/**
	 * @param string $localSecret
	 * @param string $remoteSecret
	 * @param int $timeOffset
	 * @param bool $shouldProvide
	 * @covers \TuleapWikiFarm\PreSharedKeySessionProvider::provideSessionInfo
	 * @dataProvider provideSecret
	 */
	public function testGetSessionInfo( $localSecret, $remoteSecret, $timeOffset, $shouldProvide ) {
		$config = new HashConfig( [
			'TuleapPreSharedKey' => $localSecret
		] );
		$provider = new PreSharedKeySessionProvider( $config );
		$provider->setManagerOverride( SessionManager::singleton() );

		$requestMock = $this->createMock( \WebRequest::class );
		$remoteHeader = 'Bearer ' . hash_hmac( 'sha256', $remoteSecret, time() + $timeOffset );
		$requestMock->method( 'getHeader' )->willReturnCallback(
			static function ( $name ) use ( $remoteHeader ) {
				if ( $name === 'Authorization' ) {
					return $remoteHeader;
				}
				return '';
			}
		);

		// Should not run if not in REST context
		if ( !defined( 'MW_REST_API' ) ) {
			$info = $provider->provideSessionInfo( $requestMock );
			$this->assertNull( $info );
			define( 'MW_REST_API', 1 );
		}

		$info = $provider->provideSessionInfo( $requestMock );

		if ( $shouldProvide ) {
			$this->assertInstanceOf( SessionInfo::class, $info );
		} else {
			$this->assertNull( $info );
		}
	}

	/**
	 * @return array[]
	 */
	public static function provideSecret() {
		return [
			'proper-secret' => [
				'e9971341f03fdc2021f72a89d5a12187',
				'e9971341f03fdc2021f72a89d5a12187',
				0,
				true
			],
			'mismatched-secret' => [
				'e9971341f03fdc2021f72a89d5a12187',
				'INVALID',
				0,
				false
			],
			'time-leeway-issue' => [
				'e9971341f03fdc2021f72a89d5a12187',
				'e9971341f03fdc2021f72a89d5a12187',
				15,
				false
			],
		];
	}
}
