<?php

namespace MWUnit\Assertion;

class IsInteger extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "is_integer";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}

	/**
	 * Returns false if and only if $haystack is not an integer.
	 *
	 * @param string &$failure_message
	 * @param string $haystack
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( string &$failure_message, string $haystack, $message = null ) {
		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-is-integer", $haystack )->plain();

		return preg_match( "/^\-?[0-9]+$/", $haystack ) === 1;
	}
}
