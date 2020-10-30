<?php

namespace MWUnit\Assertion;

class EqualsIgnoreCase extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "equals_ignore_case";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}

	/**
	 * Returns false if and only if the two variables, $expected and $actual are not identical to each
	 * other. Differences in casing are ignored in the comparison.
	 *
	 * @param string &$failure_message
	 * @param string $expected
	 * @param string $actual
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( string &$failure_message, string $expected, string $actual, $message = null ) {
		$expected_lower = strtolower( $expected );
		$actual_lower = strtolower( $actual );

		$failure_message = $message ??
			sprintf(
				wfMessage( "mwunit-assert-failure-equal" )->plain() . "\n\n%s",
				Equals::createDiff( $expected, $actual )
			);

		return $expected_lower === $actual_lower;
	}
}
