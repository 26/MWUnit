<?php

namespace MWUnit\Assertion;

use MWUnit\TestCaseRun;

class EqualsIgnoreCase implements Assertion {
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

		$expected_lower = strtolower( $expected );
		$actual_lower = strtolower( $actual );

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			sprintf(
				wfMessage( "mwunit-assert-failure-equal" )->plain() . "\n\n%s",
				Equals::createDiff( $expected, $actual )
			);

		Assert::report( $expected_lower === $actual_lower, $failure_message );
	}
}
