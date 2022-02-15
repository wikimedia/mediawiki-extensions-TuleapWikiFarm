<?php

namespace TuleapWikiFarm\Tests;

use MediaWiki\Session\SessionInfo;
use MediaWiki\Session\SessionManager;
use PHPUnit\Framework\TestCase;
use TuleapWikiFarm\PreSharedKeySessionProvider;

class PreSharedKeySessionProviderTest extends TestCase {
	/**
	 * @param string $localSecret
	 * @param string $remoteHeader
	 * @param bool $shouldProvide
	 * @covers \TuleapWikiFarm\PreSharedKeySessionProvider::provideSessionInfo
	 * @dataProvider provideSecret
	 */
	public function testGetSessionInfo( $localSecret, $remoteHeader, $shouldProvide ) {
		$provider = new PreSharedKeySessionProvider();
		$configMock = $this->createMock( \Config::class );
		$configMock->method( 'get' )->willReturnCallback( function ( $name ) use ( $localSecret ) {
			if ( $name === 'TuleapPreSharedKey' ) {
				return $localSecret;
			}
			return '';
		} );
		$provider->setConfig( $configMock );
		$provider->setManager( SessionManager::singleton() );

		$requestMock = $this->createMock( \WebRequest::class );
		$requestMock->method( 'getHeader' )->willReturnCallback(
			function ( $name ) use ( $remoteHeader ) {
				if ( $name === 'Authorization' ) {
					return $remoteHeader;
				}
				return '';
			}
		);

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
	public function provideSecret() {
		return [
			'proper-secret' => [
				'e9971341f03fdc2021f72a89d5a12187',
				'Bearer ' . hash_hmac( 'sha256', 'e9971341f03fdc2021f72a89d5a12187', time() ),
				true
			],
			'mismatched-secret' => [
				'e9971341f03fdc2021f72a89d5a12187',
				'Bearer ' . hash_hmac( 'sha256', 'INVALID', time() ),
				false
			],
		];
	}
}
