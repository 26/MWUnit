<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\IsEmpty;

/**
 * Class IsEmptyTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\IsEmpty
 */
class IsEmptyTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\IsEmpty::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( IsEmpty::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\IsEmpty::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( IsEmpty::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			IsEmpty::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\IsEmpty::assert
	 */
	public function testAssert() {
		$message = "foobar";
		$empty_strings = [ "", " ", "  ", "   " ];

		foreach ( $empty_strings as $empty ) {
			$f = "";

			$this->assertTrue( IsEmpty::assert(
				$f,
				$empty
			), "Failed asserting that $empty is empty" );
		}

		for ( $i = 0; $i < 100; $i++ ) {
			$f = "";

			$haystack = md5( rand() );

			$this->assertFalse( IsEmpty::assert(
				$f,
				$haystack,
				$message
			), "Failed asserting that $haystack is not empty" );

			$this->assertSame( $message, $f );
		}
	}
}
