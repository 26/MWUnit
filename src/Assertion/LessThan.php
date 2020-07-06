<?php

namespace MWUnit\Assertion;

use MWUnit\TestCaseRun;

class LessThan implements Assertion {
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

		$a = trim( $frame->expand( $args[0] ) );
		$b = trim( $frame->expand( $args[1] ) );

		if ( !is_numeric( $a ) || !is_numeric( $b ) ) {
			TestCaseRun::$test_result->setRisky();
			TestCaseRun::$test_result->setRiskyMessage( 'mwunit-invalid-assertion' );
			return;
		}

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			wfMessage( "mwunit-assert-failure-less-than", $a, $b )->plain();

		Assert::report( (float)$a < (float)$b, $failure_message );
	}
}
