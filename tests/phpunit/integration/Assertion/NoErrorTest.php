<?php

namespace MWUnit\Tests\Integration\Assertion;

use MediaWikiTestCase;
use MWUnit\Assertion\NoError;

/**
 * Class NoErrorTest
 *
 * @package MWUnit\Tests\Integration\Assertion
 * @group Assertion
 * @covers \MWUnit\Assertion\NoError
 */
class NoErrorTest extends MediaWikiTestCase {
	const NO_BOOKKEEPING_PARAMS = 2; // phpcs:ignore

	/**
	 * @covers \MWUnit\Assertion\NoError::shouldRegister
	 */
	public function testShouldRegister() {
		$this->assertTrue( NoError::shouldRegister() );
	}

	/**
	 * @covers \MWUnit\Assertion\NoError::getRequiredArgumentCount
	 * @throws \ReflectionException
	 */
	public function testGetRequiredArgumentCount() {
		$reflection_function = new \ReflectionMethod( NoError::class, 'assert' );
		$arguments_required = $reflection_function->getNumberOfParameters();

		$this->assertSame(
			NoError::getRequiredArgumentCount(),
			$arguments_required - self::NO_BOOKKEEPING_PARAMS
		);
	}

	/**
	 * @covers \MWUnit\Assertion\NoError::assert
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

				$this->assertFalse( NoError::assert(
					$f,
					$error_content,
					$message
				), "Failed asserting that $error_content contains no error" );

				$this->assertFalse( NoError::assert(
					$f,
					$error_selfclosed,
					$message
				), "Failed asserting that $error_selfclosed contains no error" );

				$this->assertTrue( NoError::assert(
					$f,
					$no_error_content,
					$message
				), "Failed asserting that $no_error_content contains an error" );

				$this->assertTrue( NoError::assert(
					$f,
					$no_error_selfclosed,
					$message
				), "Failed asserting that $no_error_selfclosed contains an error" );

				$this->assertSame( $message, $f );
			}
		}
	}
}
