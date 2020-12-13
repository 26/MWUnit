<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\StringContains;

/**
 * Class StringContainsTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\StringContains
 */
class StringContainsTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\StringContains::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( StringContains::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\StringContains::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( StringContains::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			StringContains::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\StringContains::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = 0; $i < 100; $i++ ) {
			$f = "";

			$haystack = md5( rand() );

			$haystack_length = strlen( $haystack );

			$needle_start = rand( 0, $haystack_length - 1 );
			$needle_length = rand( 1, $haystack_length - $needle_start );

			$needle = substr( $haystack, $needle_start, $needle_length );

			$result = StringContains::assert( $f, $needle, $haystack, $message );

			$this->assertTrue( $result, "Failed asserting that $haystack contains $needle" );
			$this->assertSame( $message, $f );
		}
	}
}
