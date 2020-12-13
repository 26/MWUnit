<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\NotEmpty;

/**
 * Class NotEmptyTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\NotEmpty
 */
class NotEmptyTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\NotEmpty::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( NotEmpty::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\NotEmpty::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( NotEmpty::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			NotEmpty::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\NotEmpty::assert
	 */
	public function testAssert() {
		$message = "foobar";
		$empty_strings = [ "", " ", "  ", "   " ];

		foreach ( $empty_strings as $empty ) {
			$f = "";

			$this->assertFalse( NotEmpty::assert(
				$f,
				$empty
			), "Failed asserting that $empty is empty" );
		}

		for ( $i = 0; $i < 100; $i++ ) {
			$haystack = md5( rand() );

			$f = "";

			$this->assertTrue( NotEmpty::assert(
				$f,
				$haystack,
				$message
			), "Failed asserting that $haystack is not empty" );

			$this->assertSame( $message, $f );
		}
	}
}
