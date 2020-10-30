<?php

namespace MWUnit\Assertion;

class NotEmpty extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "not_empty";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}

	/**
	 * Returns false if and only if $haystack is empty.
	 *
	 * @param string &$failure_message
	 * @param string $haystack
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( string &$failure_message, string $haystack, $message = null ) {
		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-not-empty", $haystack )->plain();

		return !empty( trim( $haystack ) );
	}
}
