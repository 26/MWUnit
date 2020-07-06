<?php

namespace MWUnit\Assertion;

use MWUnit\TestCaseRun;

class StringStartsWith implements Assertion {
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

		$needle = trim( $frame->expand( $args[0] ) );
		$haystack = trim( $frame->expand( $args[1] ) );

		$needle_length = strlen( $needle );

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			wfMessage( "mwunit-assert-failure-string-starts-with", $needle, $haystack )->plain();

		Assert::report( $needle_length <= strlen( $haystack ) &&
			substr( $haystack, 0, $needle_length ) === $needle, $failure_message );
	}
}
