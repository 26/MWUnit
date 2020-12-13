<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\IsNumeric;

/**
 * Class IsNumericTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\IsNumeric
 */
class IsNumericTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\IsNumeric::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( IsNumeric::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\IsNumeric::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( IsNumeric::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			IsNumeric::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\IsNumeric::assert
	 */
	public function testAssert() {
		$message = "foobar";
		$numeric = [
			"42",
			"1337",
			"02471",
			"1337e0",
			"02471",
			"1337e0"
		];

		$not_numeric = [
			"not numeric",
		];

		foreach ( $numeric as $num ) {
			$f = "";

			$this->assertTrue( IsNumeric::assert(
				$f,
				$num,
				$message
			), "Failed asserting that $num is numeric" );
			$this->assertSame( $message, $f );
		}

		foreach ( $not_numeric as $not_num ) {
			$f = "";

			$this->assertFalse( IsNumeric::assert(
				$f,
				$not_num,
				$message
			), "Failed asserting that $not_num is not numeric" );
			$this->assertSame( $message, $f );
		}
	}
}
