<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\GreaterThan;

/**
 * Class GreaterThanTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\GreaterThanOrEqual
 */
class GreaterThanTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\GreaterThan::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( GreaterThan::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\GreaterThan::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( GreaterThan::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			GreaterThan::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\GreaterThan::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = 1; $i < 100; $i++ ) {
			for ( $j = $i + 1; $j < $i + 100; $j++ ) {
				$f = "";

				$result = GreaterThan::assert(
					$f,
					$j,
					$i,
					$message
				);

				$this->assertTrue( $result, "Failed asserting that $i is greater than $j" );
				$this->assertSame( $message, $f );
			}

			for ( $j = 0; $j <= $i; $j++ ) {
				$result = GreaterThan::assert(
					$f,
					$j,
					$i,
					$message
				);

				$this->assertFalse( $result, "Failed asserting that $i is not greater than $j" );
				$this->assertSame( $message, $f );
			}
		}

		$this->assertNull( GreaterThan::assert(
			$f,
			"foobar",
			"1"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );

		$this->assertNull( GreaterThan::assert(
			$f,
			"1",
			"foobar"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );

		$this->assertNull( GreaterThan::assert(
			$f,
			"foobar",
			"foobar"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );
	}
}
