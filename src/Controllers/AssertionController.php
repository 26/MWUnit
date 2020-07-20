<?php

namespace MWUnit\Controllers;

use MWUnit\Assertion\Equals;
use MWUnit\Assertion\EqualsIgnoreCase;
use MWUnit\Assertion\Error;
use MWUnit\Assertion\GreaterThan;
use MWUnit\Assertion\GreaterThanOrEqual;
use MWUnit\Assertion\HasLength;
use MWUnit\Assertion\IsEmpty;
use MWUnit\Assertion\IsInteger;
use MWUnit\Assertion\IsNumeric;
use MWUnit\Assertion\LessThan;
use MWUnit\Assertion\LessThanOrEqual;
use MWUnit\Assertion\NoError;
use MWUnit\Assertion\NotEmpty;
use MWUnit\Assertion\PageExists;
use MWUnit\Assertion\SemanticMediaWiki\HasProperty;
use MWUnit\Assertion\SemanticMediaWiki\PropertyHasValue;
use MWUnit\Assertion\StringContains;
use MWUnit\Assertion\StringContainsIgnoreCase;
use MWUnit\Assertion\StringEndsWith;
use MWUnit\Assertion\StringStartsWith;
use MWUnit\Assertion\That;
use MWUnit\TestCaseRun;

final class AssertionController {
	public static function doAssert( \Parser $parser, \PPFrame $frame, array $arguments, $class ) {
		if ( !TestCaseRun::$test_result->didTestSucceed() ) {
			return;
		}

		$argument_count_callable = [ $class, 'getRequiredArgumentCount' ];

		if ( count( $arguments ) < $argument_count_callable() ) {
			TestCaseRun::$test_result->setRiskyMessage( 'mwunit-invalid-assertion' );
			TestCaseRun::$test_result->setRisky();
			return;
		}

		$failure_message = '';

		$assert_callable = [ $class, 'assert' ];
		$result = $assert_callable( $parser, $frame, $arguments, $failure_message );

		if ( $result === null ) {
			TestCaseRun::$test_result->setRiskyMessage( $failure_message );
			TestCaseRun::$test_result->setRisky();
		}

		TestCaseRun::$test_result->addAssertionResult([
			'predicate_result' => $result,
			'failure_message' => $failure_message
		]);
	}

	public static function assertEquals( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, Equals::class );
	}

	public static function assertEqualsIgnoreCase( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, EqualsIgnoreCase::class );
	}

	public static function assertError( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, Error::class );
	}

	public static function assertGreaterThan( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, GreaterThan::class );
	}

	public static function assertGreaterThanOrEqual( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, GreaterThanOrEqual::class );
	}

	public static function assertHasLength( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, HasLength::class );
	}

	public static function assertEmpty( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, IsEmpty::class );
	}

	public static function assertIsInteger( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, IsInteger::class );
	}

	public static function assertIsNumeric( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, IsNumeric::class );
	}

	public static function assertLessThan( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, LessThan::class );
	}

	public static function assertLessThanOrEqual( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, LessThanOrEqual::class );
	}

	public static function assertNoError( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, NoError::class );
	}

	public static function assertNotEmpty( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, NotEmpty::class );
	}

	public static function assertPageExists( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, PageExists::class );
	}

	public static function assertStringContains( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, StringContains::class );
	}

	public static function assertStringContainsIgnoreCase( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, StringContainsIgnoreCase::class );
	}

	public static function assertStringEndsWith( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, StringEndsWith::class );
	}

	public static function assertStringStartsWith( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, StringStartsWith::class );
	}

	public static function assertThat( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, That::class );
	}

	public static function assertHasProperty( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, HasProperty::class );
	}

	public static function assertPropertyHasValue( \Parser $parser, \PPFrame $frame, array $args ) {
		self::doAssert( $parser, $frame, $args, PropertyHasValue::class );
	}
}