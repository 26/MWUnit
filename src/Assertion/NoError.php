<?php

namespace MWUnit\Assertion;

use MWUnit\TestCaseRun;

class NoError implements Assertion {
	use Assert;

	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args ) {
		// At least one assertion already failed
		if ( !TestCaseRun::$test_result->didTestSucceed() ) {
			return;
		}

		if ( !isset( $args[0] ) ) {
			TestCaseRun::$test_result->setRisky();
			TestCaseRun::$test_result->setRiskyMessage( 'mwunit-invalid-assertion' );
			return;
		}

		$haystack = trim( $frame->expand( $args[0] ) );
		$failure_message = isset( $args[1] ) ?
			trim( $frame->expand( $args[1] ) ) :
			wfMessage( "mwunit-assert-failure-no-error" )->plain();

		Assert::report( !preg_match(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$haystack ), $failure_message );
	}
}
