<?php

namespace MWUnit\Assertion;

use MWUnit\TestCaseRun;

class PageExists implements Assertion {
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

		$page_name = trim( $frame->expand( $args[0] ) );
		$failure_message = isset( $args[1] ) ?
			trim( $frame->expand( $args[1] ) ) :
			wfMessage( "mwunit-assert-failure-page-exists" )->plain();

		$title = \Title::newFromText( $page_name );

		Assert::report( $title !== null &&
			$title !== false &&
			$title->exists(), $failure_message );
	}
}
