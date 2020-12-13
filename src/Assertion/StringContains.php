<?php

namespace MWUnit\Assertion;

class StringContains extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "string_contains";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}

	/**
	 * Returns false if and only if $needle is not contained within $haystack.
	 *
	 * @param string &$failure_message
	 * @param string $needle
	 * @param string $haystack
	 * @param string|null $message
	 * @return bool|null
	 */
	public static function assert( string &$failure_message, string $needle, string $haystack, $message = null ) {
		if ( mb_strlen( $needle ) < 1 || mb_strlen( $haystack ) < 1 ) {
			$failure_message = wfMessage( "mwunit-invalid-assertion" )->plain();
			return null;
		}

		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-contains-string", $needle, $haystack )->plain();

		return mb_strpos( $haystack, $needle ) !== false;
	}
}
