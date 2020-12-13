<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\LessThanOrEqual;

/**
 * Class LessThanOrEqualTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\LessThanOrEqual
 */
class LessThanOrEqualTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\LessThanOrEqual::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( LessThanOrEqual::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\LessThanOrEqual::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( LessThanOrEqual::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			LessThanOrEqual::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\LessThanOrEqual::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = 0; $i < 100; $i++ ) {
			for ( $j = 0; $j < $i; $j++ ) {
				$f = "";

				$result = LessThanOrEqual::assert(
					$f,
					$j,
					$i,
					$message
				);

				$this->assertTrue( $result, "Failed asserting that $i is less than or equal to $j" );
				$this->assertSame( $message, $f );
			}

			for ( $j = $i + 1; $j < $i + 101; $j++ ) {
				$f = "";

				$result = LessThanOrEqual::assert(
					$f,
					$j,
					$i,
					$message
				);

				$this->assertFalse( $result, "Failed asserting that $i is not less than or equal to $j" );
				$this->assertSame( $message, $f );
			}
		}

		$this->assertNull( LessThanOrEqual::assert(
			$f,
			"foobar",
			"1"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );

		$this->assertNull( LessThanOrEqual::assert(
			$f,
			"1",
			"foobar"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );

		$this->assertNull( LessThanOrEqual::assert(
			$f,
			"foobar",
			"foobar"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );
	}
}
