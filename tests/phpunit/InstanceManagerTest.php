<?php

namespace TuleapWikiFarm\Tests;

use HashConfig;
use PHPUnit\Framework\TestCase;
use TuleapWikiFarm\InstanceEntity;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\InstanceStore;

class InstanceManagerTest extends TestCase {
	/**
	 * @var InstanceManager
	 */
	private $manager;

	protected function setUp(): void {
		parent::setUp();

		$storeMock = $this->createMock( InstanceStore::class );
		$this->manager = new InstanceManager( $storeMock, new HashConfig( [] ) );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceManager::checkInstanceNameValidity
	 * @dataProvider provideInstanceNames
	 */
	public function testInstanceNameValidity( $name, $shouldBeValid ) {
		$this->assertSame( $shouldBeValid, $this->manager->checkInstanceNameValidity( $name ) );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceManager::generateScriptPath
	 */
	public function testGenerateScriptPath() {
		$instanceMock = $this->createMock( InstanceEntity::class );
		$instanceMock->method( 'getName' )->willReturn( 'Foo_bar' );
		$this->assertSame( '/mediawiki/Foo-bar', $this->manager->generateScriptPath( $instanceMock ) );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceManager::getNewInstance
	 */
	public function testGetNewInstance() {
		$instance = $this->manager->getNewInstance( 'Foo', 101 );
		$this->assertInstanceOf( InstanceEntity::class, $instance );
		$this->assertSame( 'Foo', $instance->getName() );
		$this->assertSame( 101, $instance->getId() );
	}

	/**
	 * @return array[]
	 */
	public function provideInstanceNames() {
		return [
			[ 'Dummy', true ],
			[ 'Foo/Bar', false ],
			[ 'Foo-bar', true ],
			[ 'Foo#bar', false ],
			[ 'Foo_bar', true ],
			[ '<foo>', false ],
		];
	}
}
