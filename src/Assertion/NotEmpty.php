<?php

namespace MWUnit\Assertion;

class NotEmpty implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "not_empty";
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
	 * Returns false if and only if $haystack is empty.
	 *
	 * @param string $failure_message
	 * @param string $haystack
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( &$failure_message, $haystack, $message = null ) {
		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-not-empty", $haystack )->plain();

		return !empty( $haystack );
	}
}
