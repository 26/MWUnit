<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\HasLength;

/**
 * Class HasLengthTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\HasLength
 */
class HasLengthTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\HasLength::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( HasLength::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\HasLength::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( HasLength::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			HasLength::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\HasLength::assert
	 */
	public function testAssert() {
		$message = "foobar";

		for ( $i = 1; $i < 25; $i++ ) {
			$f = "";

			$string = substr( md5( rand() ), 0, $i );

			$this->assertTrue( HasLength::assert(
				$f,
				$string,
				(string)$i,
				$message
			), "Failed asserting that $string is $i characters long" );
			$this->assertSame( $message, $f );
		}

		$this->assertNull( HasLength::assert(
			$f,
			"foobar",
			"foobar"
		), "Failed asserting that 'foobar' is not numeric" );
		$this->assertNotEmpty( $f );
	}
}
