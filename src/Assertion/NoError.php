<?php

namespace MWUnit\Assertion;

class NoError implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "no_error";
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
	public static function assert( &$failure_message, $haystack, $message = null ) {
		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-no-error" )->plain();

		return preg_match(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$haystack ) !== 1;
	}
}
