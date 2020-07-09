<?php

namespace MWUnit\Assertion;

use MWUnit\TestCaseRun;

/**
 * Trait Assert
 * @package MWUnit\Assertion
 */
trait Assert {
	/**
	 * Takes a predicate and an error message and reports the result of the predicate to the current
	 * TestCaseRunner.
	 *
	 * @param bool $predicate
	 * @param string $failure_message
	 * @return bool
	 */
	public static function report( bool $predicate, string $failure_message ): bool {
		if ( !$predicate ) {
			TestCaseRun::$test_result->addAssertionResult(
				[
					'predicate_result' => false,
					'failure_message' => $failure_message
				]
			);
		} else {
			TestCaseRun::$test_result->addAssertionResult(
				[
					'predicate_result' => true
				]
			);
		}

		return $predicate;
	}
}
