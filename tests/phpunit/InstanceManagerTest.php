<?php

namespace TuleapWikiFarm\Tests;

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
		$this->manager = new InstanceManager( $storeMock );
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
		$this->assertSame( '/Foo-bar', $this->manager->generateScriptPath( $instanceMock ) );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceManager::getNewInstance
	 */
	public function testGetNewInstance() {
		$instance = $this->manager->getNewInstance( 'Foo' );
		$this->assertInstanceOf( InstanceEntity::class, $instance );
		$this->assertSame( 'Foo', $instance->getName() );
		$this->assertNull( $instance->getId() );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceManager::getRenamedInstanceEntity
	 */
	public function testGetRenamedInstance() {
		$instanceMock = $this->createMock( InstanceEntity::class );
		$instanceMock->method( 'getName' )->willReturn( 'Foo' );
		$instanceMock->method( 'getId' )->willReturn( 3 );
		$instanceMock->method( 'getDatabaseName' )->willReturn( 'bar' );

		$renamed = $this->manager->getRenamedInstanceEntity( $instanceMock, 'Bar' );
		$this->assertInstanceOf( InstanceEntity::class, $renamed );
		$this->assertSame( 'Bar', $renamed->getName() );
		$this->assertSame( 3, $renamed->getId() );
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
