<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\StringEndsWith;

/**
 * Class StringEndsWithTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\StringEndsWith
 */
class StringEndsWithTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\StringEndsWith::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( StringEndsWith::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\StringEndsWith::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( StringEndsWith::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			StringEndsWith::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\StringEndsWith::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = 0; $i < 100; $i++ ) {
			$f = "";

			$haystack = md5( rand() );

			$needle_length = rand( 1, strlen( $haystack ) );
			$needle = substr( $haystack, -$needle_length, $needle_length );

			$result = StringEndsWith::assert( $f, $needle, $haystack, $message );

			$this->assertTrue( $result, "Failed asserting that $haystack ends in $needle" );
			$this->assertSame( $message, $f );
		}
	}
}
