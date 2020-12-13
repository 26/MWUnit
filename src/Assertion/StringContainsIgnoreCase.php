<?php

namespace MWUnit\Assertion;

class StringContainsIgnoreCase extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "string_contains_ignore_case";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}

	/**
	 * Returns false if and only if $needle is not contained within $haystack. Differences in casing
	 * are ignored in the comparison.
	 *
	 * @param string &$failure_message
	 * @param string $needle
	 * @param string $haystack
	 * @param string|null $message
	 * @return bool|null
	 */
	public static function assert( string &$failure_message, string $needle, string $haystack, $message = null ) {
		$needle_lower = mb_strtolower( $needle );
		$haystack_lower = mb_strtolower( $haystack );

		if ( mb_strlen( $needle_lower ) < 1 || mb_strlen( $haystack_lower ) < 1 ) {
			$failure_message = wfMessage( "mwunit-invalid-assertion" )->plain();
			return null;
		}

		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-contains-string", $needle_lower, $haystack_lower )->plain();

		return mb_strpos( $haystack_lower, $needle_lower ) !== false;
	}
}
