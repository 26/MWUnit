<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\IsInteger;

/**
 * Class IsIntegerTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\IsInteger
 */
class IsIntegerTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\IsInteger::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( IsInteger::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\IsInteger::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( IsInteger::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			IsInteger::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\IsInteger::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = -100; $i < 100; $i++ ) {
			$f = "";

			$integer = (string)$i;
			$non_integer = md5( rand() );

			$this->assertTrue( IsInteger::assert(
				$f,
				$integer,
				$message
			), "Failed asserting that $integer is an integer" );
			$this->assertSame( $message, $f );

			$this->assertFalse( IsInteger::assert(
				$f,
				$non_integer,
				$message
			), "Failed asserting that $non_integer is not an integer" );
			$this->assertSame( $message, $f );
		}
	}
}
