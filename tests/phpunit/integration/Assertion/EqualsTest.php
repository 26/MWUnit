<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\Equals;

/**
 * Class ErrorTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\Error
 */
class EqualsTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\Equals::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( Equals::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\Equals::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( Equals::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			Equals::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\Equals::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = 0; $i < 1000; $i++ ) {
			$a = md5( rand() );
			$b = md5( rand() );

			$f = "";

			$this->assertTrue( Equals::assert(
				$f,
				$a,
				$a,
				$message
			), "Failed asserting that $a is equal to $a" );
			$this->assertSame( $message, $f );

			$this->assertFalse( Equals::assert(
				$f,
				$a,
				$b,
				$message
			), "Failed asserting that $a is not equal to $b" );
			$this->assertSame( $message, $f );
		}
	}
}
