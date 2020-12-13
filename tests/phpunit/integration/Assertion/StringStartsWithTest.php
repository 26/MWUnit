<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\StringStartsWith;

/**
 * Class StringStartsWithTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\StringStartsWith
 */
class StringStartsWithTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\StringStartsWith::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( StringStartsWith::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\StringStartsWith::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( StringStartsWith::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			StringStartsWith::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\StringStartsWith::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = 0; $i < 100; $i++ ) {
			$f = "";

			$haystack = md5( rand() );
			$needle = substr( $haystack, 0, rand( 1, strlen( $haystack ) ) );

			$result = StringStartsWith::assert( $f, $needle, $haystack, $message );

			$this->assertTrue( $result, "Failed asserting that $haystack starts with $needle" );
			$this->assertSame( $message, $f );
		}
	}
}
