<?php

namespace MWUnit\Controller;

use MWUnit\Assertion\Assertion;
use MWUnit\MWUnit;
use MWUnit\TestRun;

class AssertionController {
	/**
	 * Gets called when the parser parses an assertion parser function. Handles the assertion
	 * and reports the result to TestCaseRun when applicable.
	 *
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 * @param array $arguments
	 * @param string $class The class corresponding to this assertion
	 * @return string
	 */
	public static function handleAssertionParserHook(
		\Parser $parser,
		\PPFrame $frame,
		array $arguments,
		$class
	) {
		if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
			return MWUnit::error( "mwunit-outside-test-namespace" );
		}

		if ( !MWUnit::isRunning() ) { return '';
		}
		if ( !TestRun::$test_result->didTestSucceed() ) { return '';
		}

		$required_arg_count = $class::getRequiredArgumentCount();
		$actual_arg_count 	= count( $arguments );
		$argument_range 	= range( $required_arg_count, $required_arg_count + 1 );

		if ( !in_array( $actual_arg_count, $argument_range ) ) {
			TestRun::$test_result->setRisky( wfMessage( 'mwunit-invalid-assertion' )->plain() );
			return '';
		}

		self::callAssertion( self::expandArguments( $frame, $arguments ), $class );

		return '';
	}

	/**
	 * Calls the "assert" method on the given class with the given arguments and handles
	 * the response. This function also adds the assertion result to the TestCaseRun object.
	 *
	 * @param array $arguments The arguments to pass to the assert method
	 * @param string $class The name of the class on which the assert method should be called
	 *
	 * @return bool Returns the result of the assertion, or null on failure
	 */
	private static function callAssertion( array $arguments, $class ) {
		try {
			$reflection = new \ReflectionClass( $class );
		} catch ( \ReflectionException $e ) {
			return null;
		}

		if ( !$reflection->implementsInterface( Assertion::class ) ) {
			return null;
		}

		$failure_message = '';
		$test_result = TestRun::$test_result;
		$result = $class::assert( $failure_message, ...$arguments );

		if ( $result === null ) {
			$test_result->setRisky( $failure_message );
		} elseif ( $result === false ) {
			$test_result->setFailed( $failure_message );
		}

		$test_result->incrementAssertionCount();

		return $result;
	}

	/**
	 * Expands the array of given arguments using the given PPFrame.
	 *
	 * @param \PPFrame $frame The PPFrame to use for expansion
	 * @param array $arguments The array of unexpanded arguments
	 * @return array The given arguments expanded
	 */
	private static function expandArguments( \PPFrame $frame, array $arguments ) {
		return array_map( function ( $argument ) use ( $frame ) {
			return trim( $frame->expand( $argument ) );
		}, $arguments );
	}
}
