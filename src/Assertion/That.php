<?php

namespace MWUnit\Assertion;

class That extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "that";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}

	/**
	 * Returns false if and only if $proposition is not one of the following
	 * values:
	 *
	 * - true
	 * - yes
	 * - on
	 * - 1
	 *
	 * @param string &$failure_message
	 * @param string $proposition
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( string &$failure_message, string $proposition, $message = null ) {
		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-that", $proposition )->plain();

		return filter_var( $proposition, FILTER_VALIDATE_BOOLEAN );
	}
}
