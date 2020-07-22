<?php

namespace MWUnit\Assertion;

class StringEndsWith implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "string_ends_with";
	}

	/**
	 * @inheritDoc
	 */
	public static function shouldRegister(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}

	/**
	 * Returns false if and only if $needle is not at the end of $haystack.
	 *
	 * @param string $failure_message
	 * @param string $needle
	 * @param string $haystack
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( &$failure_message, $needle, $haystack, $message = null ) {
		$needle_length = strlen( $needle );

		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-string-ends-with", $needle, $haystack )->plain();

		return $needle_length <= strlen( $haystack ) &&
			substr( $haystack, -$needle_length, $needle_length ) === $needle;
	}
}
