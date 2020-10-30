<?php

namespace MWUnit\Assertion;

class IsNumeric extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "is_numeric";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}

	/**
	 * Returns false if and only if $haystack is not numeric.
	 *
	 * @see https://www.php.net/manual/en/function.is-numeric.php
	 *
	 * @param string &$failure_message
	 * @param string $haystack
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( string &$failure_message, string $haystack, $message = null ) {
		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-is-numeric", $haystack )->plain();

		return is_numeric( $haystack );
	}
}
