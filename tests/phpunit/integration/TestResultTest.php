<?php

namespace MWUnit\Tests\Integration;

use MediaWikiIntegrationTestCase;
use MWUnit\TestResult;

/**
 * Class TestResultTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group TestResult
 * @covers \MWUnit\TestResult
 */
class TestResultTest extends MediaWikiIntegrationTestCase {
	const CANONICAL_TESTNAME = "TestResult::Test";

	public function testSetFailed() {
		$test_result = new TestResult( self::CANONICAL_TESTNAME );

		$this->assertSame( TestResult::T_SUCCESS, $test_result->getResult(), "Failed to assert that initial result value is SUCCESS" );

		$test_result->setFailed( 'foobar' );

		$this->assertSame( TestResult::T_FAILED, $test_result->getResult(), "Failed asserting that status got updated after call to setFailed()" );
		$this->assertSame( 'foobar', $test_result->getFailureMessage(), "Failed asserting that failure message got set after call to setFailed()" );
	}

	public function testSetRisky() {
		$test_result = new TestResult( self::CANONICAL_TESTNAME );

		$this->assertSame( TestResult::T_SUCCESS, $test_result->getResult(), "Failed to assert that initial result value is SUCCESS" );

		$test_result->setRisky( 'foobar' );

		$this->assertSame( TestResult::T_RISKY, $test_result->getResult(), "Failed asserting that status got updated after call to setRisky()" );
		$this->assertSame( 'foobar', $test_result->getRiskyMessage(), "Failed asserting that risky message got set after call to setRisky()" );
	}

	/**
	 * @covers \MWUnit\TestResult::toString
	 */
	public function testToString() {
		$test_result = new TestResult( self::CANONICAL_TESTNAME );

		$this->assertEquals( ".", $test_result->toString() );

		$test_result->setRisky( "" );
		$this->assertEquals( "\033[43mR\033[0m", $test_result->toString() );

		$test_result->setFailed( "" );
		$this->assertEquals( "\033[41mF\033[0m", $test_result->toString() );
	}
}
