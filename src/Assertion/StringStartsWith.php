<?php

namespace MWUnit\Assertion;

class StringStartsWith extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "string_starts_with";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}

	/**
	 * Returns false if and only if $needle is not at the start of $haystack.
	 *
	 * @param string &$failure_message
	 * @param string $needle
	 * @param string $haystack
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( string &$failure_message, string $needle, string $haystack, $message = null ) {
		$needle_length = strlen( $needle );

		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-string-starts-with", $needle, $haystack )->plain();

		return $needle_length <= strlen( $haystack ) &&
			substr( $haystack, 0, $needle_length ) === $needle;
	}
}
