<?php

namespace TuleapWikiFarm\Tests;

use PHPUnit\Framework\TestCase;
use TuleapWikiFarm\InstanceEntity;

class InstanceEntityTest extends TestCase {
	/**
	 * @var InstanceEntity
	 */
	private $instance;

	protected function setUp(): void {
		parent::setUp();
		$this->instance = new InstanceEntity(
			'Dummy', 101, new \DateTime(), '/101',
			'tuleap_dummy', '/dummy', InstanceEntity::STATE_INITIALIZING
		);
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceEntity::getName
	 */
	public function testGetName() {
		$this->assertSame( 'Dummy', $this->instance->getName() );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceEntity::getCreatedAt
	 */
	public function testGetCreatedAt() {
		$this->assertInstanceOf( \DateTime::class, $this->instance->getCreatedAt() );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceEntity::getId
	 */
	public function testGetId() {
		$this->assertSame( 101, $this->instance->getId() );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceEntity::getDirectory
	 */
	public function testGetDirectory() {
		$this->assertSame( '/101', $this->instance->getDirectory() );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceEntity::getDatabaseName
	 * @covers \TuleapWikiFarm\InstanceEntity::setDatabaseName
	 */
	public function testGetSetDatabaseName() {
		$this->assertSame( 'tuleap_dummy', $this->instance->getDatabaseName() );
		$this->instance->setDatabaseName( 'tuleap_foo' );
		$this->assertSame( 'tuleap_foo', $this->instance->getDatabaseName() );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceEntity::getScriptPath
	 * @covers \TuleapWikiFarm\InstanceEntity::setScriptPath
	 */
	public function testGetSetScriptPath() {
		$this->assertSame( '/dummy', $this->instance->getScriptPath() );
		$this->instance->setScriptPath( '/foo' );
		$this->assertSame( '/foo', $this->instance->getScriptPath() );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceEntity::setStatus
	 * @covers \TuleapWikiFarm\InstanceEntity::getStatus
	 */
	public function testGetSetStatus() {
		$this->assertSame( InstanceEntity::STATE_INITIALIZING, $this->instance->getStatus() );
		$this->instance->setStatus( InstanceEntity::STATE_READY );
		$this->assertSame( InstanceEntity::STATE_READY, $this->instance->getStatus() );

		// setting invalid state should not change the state
		$this->instance->setStatus( 'invalid' );
		$this->assertSame( InstanceEntity::STATE_READY, $this->instance->getStatus() );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceEntity::getDataItem
	 * @covers \TuleapWikiFarm\InstanceEntity::setDataItem
	 */
	public function testGetSetData() {
		$this->assertSame( [
			// auto added
			'project_id' => 101
		], $this->instance->getData() );

		$this->instance->setDataItem( 'foo', 'bar' );
		$this->assertSame( 'bar', $this->instance->getDataItem( 'foo' ) );
	}

	/**
	 * @covers \TuleapWikiFarm\InstanceEntity::isDirty
	 * @covers \TuleapWikiFarm\InstanceEntity::setDirty
	 */
	public function testDirty() {
		$this->instance->setDirty( false );
		$this->assertFalse( $this->instance->isDirty() );
		// Make a change to make it dirty
		$this->instance->setDatabaseName( 'tuleap_test' );
		$this->assertTrue( $this->instance->isDirty() );
		$this->instance->setDirty( false );
		$this->assertFalse( $this->instance->isDirty() );
	}
}
