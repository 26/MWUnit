<?php

namespace MWUnit\Controller;

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
use MWUnit\MWUnit;
use MWUnit\TestCaseRun;

class AssertionController {
	public static function handleAssertionParserHook(
		\Parser $parser,
		\PPFrame $frame,
		array $arguments,
		$class
	) {
		if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
			return MWUnit::error( "mwunit-outside-test-namespace" );
		}

		if ( !MWUnit::isRunning() ) {
			return [];
		}

		if ( !TestCaseRun::$test_result->didTestSucceed() ) {
			return [];
		}

		$required_arg_count = $class::getRequiredArgumentCount();
		$actual_arg_count 	= count( $arguments );

		if ( $actual_arg_count < $required_arg_count ||
			$actual_arg_count > $required_arg_count + 1 ) {
			TestCaseRun::$test_result->setRisky( wfMessage( 'mwunit-invalid-assertion' )->plain() );
			return [];
		}

		$arguments = array_map( function ( $argument ) use ( $frame ) {
			return trim( $frame->expand( $argument ) );
		}, $arguments );

		self::callAssertion( $arguments, $class );

		return [];
	}

	private static function callAssertion( array $arguments, $class ) {
		$failure_message = '';
		$result = $class::assert( $failure_message, ...$arguments );

		if ( $result === null ) {
			TestCaseRun::$test_result->setRisky( $failure_message );
		}

		TestCaseRun::$test_result->addAssertionResult( [
			'predicate_result' => $result,
			'failure_message' => $failure_message
		] );
	}
}
