<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\Error;

/**
 * Class ErrorTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\Error
 */
class ErrorTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\Error::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( Error::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\Error::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( Error::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			Error::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\Error::assert
	 */
	public function testAssert() {
		$message = "foobar";

		$valid_tags = [ "strong", "span", "p", "div" ];
		$attributes = [ "foobar", "example", "no-error" ];

		foreach ( $valid_tags as $tag ) {
			foreach ( $attributes as $attribute ) {
				$f = "";

				$error_content = sprintf(
					'<%s class="error %s">Foobar</%s>',
					$tag, $attribute, $tag
				);

				$error_selfclosed = sprintf(
					'<%s class="error %s" />',
					$tag, $attribute
				);

				$no_error_content = sprintf(
					'<%s class="%s">Foobar</%s>',
					$tag, $attribute, $tag
				);

				$no_error_selfclosed = sprintf(
					'<%s class="%s" />',
					$tag, $attribute
				);

				$this->assertTrue( Error::assert(
					$f,
					$error_content,
					$message
				), "Failed asserting that $error_content contains an error" );

				$this->assertTrue( Error::assert(
					$f,
					$error_selfclosed,
					$message
				), "Failed asserting that $error_selfclosed contains an error" );

				$this->assertFalse( Error::assert(
					$f,
					$no_error_content,
					$message
				), "Failed asserting that $no_error_content does not contain an error" );

				$this->assertFalse( Error::assert(
					$f,
					$no_error_selfclosed,
					$message
				), "Failed asserting that $no_error_selfclosed does not contain an error" );

				$this->assertSame( $message, $f );
			}
		}
	}
}
