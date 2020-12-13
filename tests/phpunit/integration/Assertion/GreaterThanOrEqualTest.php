<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\GreaterThanOrEqual;

/**
 * Class GreaterThanOrEqualTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\GreaterThanOrEqual
 */
class GreaterThanOrEqualTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\GreaterThanOrEqual::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( GreaterThanOrEqual::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\GreaterThanOrEqual::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( GreaterThanOrEqual::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			GreaterThanOrEqual::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\GreaterThanOrEqual::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = 1; $i < 100; $i++ ) {
			for ( $j = $i; $j < $i + 100; $j++ ) {
				$f = "";

				$result = GreaterThanOrEqual::assert(
					$f,
					$j,
					$i,
					$message
				);

				$this->assertTrue( $result, "Failed asserting that $i is greater than or equal to $j" );
				$this->assertSame( $message, $f );
			}

			for ( $j = 0; $j < $i; $j++ ) {
				$result = GreaterThanOrEqual::assert(
					$f,
					$j,
					$i,
					$message
				);

				$this->assertFalse( $result, "Failed asserting that $i is not greater than or equal to $j" );
				$this->assertSame( $message, $f );
			}
		}

		$this->assertNull( GreaterThanOrEqual::assert(
			$f,
			"foobar",
			"1"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );

		$this->assertNull( GreaterThanOrEqual::assert(
			$f,
			"1",
			"foobar"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );

		$this->assertNull( GreaterThanOrEqual::assert(
			$f,
			"foobar",
			"foobar"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );
	}
}
