<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\EqualsIgnoreCase;

/**
 * Class EqualsIgnoreCaseTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\EqualsIgnoreCase
 */
class EqualsIgnoreCaseTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\EqualsIgnoreCase::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( EqualsIgnoreCase::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\EqualsIgnoreCase::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( EqualsIgnoreCase::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			EqualsIgnoreCase::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\EqualsIgnoreCase::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = 0; $i < 1000; $i++ ) {
			$a = md5( rand() );
			$b = md5( rand() );

			$f = "";

			$this->assertTrue( EqualsIgnoreCase::assert(
				$f,
				$a,
				strtoupper( $a ),
				$message
			), "Failed asserting that $a is equal to $a" );
			$this->assertSame( $message, $f );

			$this->assertFalse( EqualsIgnoreCase::assert(
				$f,
				$a,
				$b,
				$message
			), "Failed asserting that $a is not equal to $b" );
			$this->assertSame( $message, $f );
		}
	}
}
