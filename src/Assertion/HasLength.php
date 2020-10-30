<?php

namespace MWUnit\Assertion;

class HasLength extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "has_length";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}

	/**
	 * Returns false if and only if $haystack is not exactly $expected_length characters in size.
	 *
	 * @param string &$failure_message
	 * @param string $haystack
	 * @param string $expected_length
	 * @param string|null $message
	 * @return bool|null
	 */
	public static function assert( string &$failure_message, string $haystack, string $expected_length, $message = null ) {
		$actual_length = mb_strlen( $haystack );

		if ( !ctype_digit( $expected_length ) ) {
			$failure_message = wfMessage( "mwunit-invalid-assertion" )->plain();
			return null;
		}

		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-has-length", $actual_length, $expected_length )->plain();

		return $actual_length === (int)$expected_length;
	}
}
