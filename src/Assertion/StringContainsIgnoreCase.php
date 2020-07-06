<?php

namespace MWUnit\Assertion;

use MWUnit\TestCaseRun;

class StringContainsIgnoreCase implements Assertion {
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

		$needle_lower = strtolower( $needle );
		$haystack_lower = strtolower( $haystack );

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			wfMessage( "mwunit-assert-failure-contains-string", $needle_lower, $haystack_lower )->plain();

		Assert::report( strpos( $haystack_lower, $needle_lower ) !== false, $failure_message );
	}
}
