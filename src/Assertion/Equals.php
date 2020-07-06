<?php

namespace MWUnit\Assertion;

use MWUnit\TestCaseRun;

class Equals implements Assertion {
	use Assert;

	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args ) {
		// At least one assertion already failed
		if ( !TestCaseRun::$test_result->didTestSucceed() ) {
			return;
		}

		if ( !isset( $args[0] ) || !isset( $args[1] ) ) {
			TestCaseRun::$test_result->setRisky();
			TestCaseRun::$test_result->setRiskyMessage( 'mwunit-invalid-assertion' );
			return;
		}

		$expected = trim( $frame->expand( $args[0] ) );
		$actual = trim( $frame->expand( $args[1] ) );

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			sprintf(
				wfMessage( "mwunit-assert-failure-equal" )->plain() . "\n\n%s",
				self::createDiff( $expected, $actual )
			);

		Assert::report( $expected === $actual, $failure_message );
	}

	/**
	 * Creates a very simple "diff" view of what was expected and what the actual value of the thing that got
	 * compared was.
	 *
	 * @param string $expected
	 * @param string $actual
	 * @return string
	 */
	public static function createDiff( string $expected, string $actual ): string {
		$expected_lines = explode( "\n", $expected );
		$actual_lines   = explode( "\n", $actual );

		$expected_formatted = implode( "\n", array_map( function ( $line ): string {
			return "- $line";
		}, $expected_lines ) );

		$actual_formatted = implode( "\n", array_map( function ( $line ): string {
			return "+ $line";
		}, $actual_lines ) );

		return sprintf( "--- " . wfMessage( "mwunit-expected" )->plain() .
			"\n+++ " . wfMessage( "mwunit-actual" )->plain() .
			"\n@@ @@\n$expected_formatted\n$actual_formatted" );
	}
}
