<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\That;

/**
 * Class ThatTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\That
 */
class ThatTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\That::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( That::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\That::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( That::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			That::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\That::assert
	 */
	public function testAssert() {
		$message = "foobar";

		$true_values = [ true, "true", "1", 1, "yes", "on" ];
		$false_values = [ false, "false", "0", 0, "no", "off" ];

		foreach ( $true_values as $true_value ) {
			$f = "";

			$result = That::assert( $f, $true_value, $message );
			$this->assertTrue( $result, "Failed to cast $true_value to `true`" );
			$this->assertSame( $message, $f );
		}

		foreach ( $false_values as $false_value ) {
			$f = "";

			$result = That::assert( $f, $false_value, $message );
			$this->assertFalse( $result, "Failed to cast $false_value to `false`" );
			$this->assertSame( $message, $f );
		}
	}
}
