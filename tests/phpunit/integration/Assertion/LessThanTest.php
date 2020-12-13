<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\LessThan;

/**
 * Class LessThanTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\LessThan
 */
class LessThanTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\LessThan::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( LessThan::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\LessThan::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( LessThan::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			LessThan::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\LessThan::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = 1; $i < 100; $i++ ) {
			for ( $j = 0; $j < $i; $j++ ) {
				$f = "";

				$result = LessThan::assert(
					$f,
					$j,
					$i,
					$message
				);

				$this->assertTrue( $result, "Failed asserting that $i is less than $j" );
				$this->assertSame( $message, $f );
			}

			for ( $j = $i; $j < $i + 100; $j++ ) {
				$f = "";

				$result = LessThan::assert(
					$f,
					$j,
					$i,
					$message
				);

				$this->assertFalse( $result, "Failed asserting that $i is not less than $j" );
				$this->assertSame( $message, $f );
			}
		}

		$f = "";

		$this->assertNull( LessThan::assert(
			$f,
			"foobar",
			"1"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );

		$this->assertNull( LessThan::assert(
			$f,
			"1",
			"foobar"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );

		$this->assertNull( LessThan::assert(
			$f,
			"foobar",
			"foobar"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );
	}
}
