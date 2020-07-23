<?php

namespace MWUnit\Tests\Integration\Registry;

use MediaWikiIntegrationTestCase;
use MWUnit\Registry\MockRegistry;
use Title;

/**
 * Class MockRegistryTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Registry
 * @covers \MWUnit\Registry\MockRegistry
 */
class MockRegistryTest extends MediaWikiIntegrationTestCase {
	/**
	 * @var MockRegistry
	 */
	private $mock_registry_instance;

	public function setUp() {
		$this->mock_registry_instance = MockRegistry::getInstance();
		parent::setUp();
	}

	/**
	 * @covers \MWUnit\Registry\MockRegistry::getInstance
	 */
	public function testGetInstance() {
		$instance = MockRegistry::getInstance();

		$this->assertInstanceOf( MockRegistry::class, $instance );
	}

	/**
	 * @covers \MWUnit\Registry\MockRegistry::registerMock
	 * @throws \Exception
	 */
	public function testRegisterMock() {
		$title_mock = $this->createMock( Title::class );
		$title_mock->method( 'getArticleID' )
			->willReturn( 1 );
		$title_mock->method( 'getFullText' )
			->willReturn( 'Main Page' );

		$this->mock_registry_instance->registerMock( $title_mock, "foobar" );

		$this->assertTrue( $this->mock_registry_instance->isMocked( $title_mock ), "Failed to assert that mock was created" );
	}

	/**
	 * @covers \MWUnit\Registry\MockRegistry::getMock
	 * @throws \Exception
	 */
	public function testGetMock() {
		$title_mock = $this->createMock( Title::class );
		$title_mock->method( 'getArticleID' )
			->willReturn( 1 );
		$title_mock->method( 'getFullText' )
			->willReturn( 'Main Page' );

		$this->mock_registry_instance->registerMock( $title_mock, "foobar" );

		$this->assertEquals(
			$this->mock_registry_instance->getMock( $title_mock ),
			"foobar",
			"Failed to assert that mock was created"
		);
	}
}