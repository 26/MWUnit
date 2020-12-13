<?php

namespace MWUnit\Assertion;

class Error extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "error";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}

	/**
	 * Returns false if and only if $haystack does not contain at least one div, strong, span or p tag with the
	 * attribute 'class="error"'. Tags with this attribute are usually returned by
	 * other parser functions.
	 *
	 * @param string &$failure_message
	 * @param string $haystack
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( string &$failure_message, string $haystack, $message = null ) {
		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-error" )->plain();

		return preg_match(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$haystack
		) === 1;
	}
}
