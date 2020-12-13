<?php

namespace MWUnit\Assertion;

class LessThan extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "less_than";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}

	/**
	 * Return false if and only if $left is not less than $right.
	 *
	 * @param string &$failure_message
	 * @param string $left
	 * @param string $right
	 * @param string|null $message
	 * @return bool|null
	 */
	public static function assert( string &$failure_message, string $left, string $right, $message = null ) {
		if ( !is_numeric( $left ) || !is_numeric( $right ) ) {
			$failure_message = wfMessage( 'mwunit-invalid-assertion' )->plain();
			return null;
		}

		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-less-than", $left, $right )->plain();

		return (float)$left < (float)$right;
	}
}
