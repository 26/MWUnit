<?php

namespace MWUnit\Assertion;

use MWUnit\TestCaseRun;

class That implements Assertion {
	use Assert;

	/**
	 * @inheritDoc
	 */
	public static function assert(\Parser $parser, \PPFrame $frame, array $args) {
		// At least one assertion already failed
		if ( !TestCaseRun::$test_result->didTestSucceed() ) {
			return;
		}

		if ( !isset( $args[0] ) ) {
			TestCaseRun::$test_result->setRisky();
			TestCaseRun::$test_result->setRiskyMessage( 'mwunit-invalid-assertion' );
			return;
		}

		$that = trim( $frame->expand( $args[0] ) );

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			wfMessage( "mwunit-assert-failure-that", $that )->plain();

		Assert::report( filter_var( $that, FILTER_VALIDATE_BOOLEAN ), $failure_message );
	}
}