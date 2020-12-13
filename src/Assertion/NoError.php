<?php

namespace MWUnit\Assertion;

class NoError extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "no_error";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}

	/**
	 * Returns false if and only if $payload contains at least one div, strong, span or p tag
	 * with the attribute 'class="error"'. Tags with this element are usually returned by other parser
	 * functions.
	 *
	 * @param string &$failure_message
	 * @param string $haystack
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( string &$failure_message, string $haystack, $message = null ) {
		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-no-error" )->plain();

		return preg_match(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$haystack ) !== 1;
	}
}
