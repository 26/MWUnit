<?php

namespace MWUnit\Assertion;

class LessThanOrEqual extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "less_than_or_equal";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}

	/**
	 * Returns false if and only if $left is not less than or equal to $right.
	 *
	 * @param string &$failure_message
	 * @param string $left
	 * @param string $right
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( string &$failure_message, string $left, string $right, $message = null ) {
		if ( !is_numeric( $left ) || !is_numeric( $right ) ) {
			$failure_message = wfMessage( 'mwunit-invalid-assertion' )->plain();
			return null;
		}

		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-less-than-or-equal", $left, $right )->plain();

		return (float)$left <= (float)$right;
	}
}
