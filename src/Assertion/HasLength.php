<?php

namespace MWUnit\Assertion;

use MWUnit\TestCaseRun;

class HasLength implements Assertion {
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

		$haystack = trim( $frame->expand( $args[0] ) );

		$actual_length = strlen( $haystack );
		$expected_length = trim( $frame->expand( $args[1] ) );

		if ( !ctype_digit( $expected_length ) ) {
			TestCaseRun::$test_result->setRisky();
			return;
		}

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			wfMessage( "mwunit-assert-failure-has-length", $actual_length, $expected_length )->plain();

		Assert::report( $actual_length === (int)$expected_length, $failure_message );
	}
}
