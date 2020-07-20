<?php

namespace MWUnit\Assertion;

class HasLength implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$haystack = trim( $frame->expand( $args[0] ) );

		$actual_length = strlen( $haystack );
		$expected_length = trim( $frame->expand( $args[1] ) );

		if ( !ctype_digit( $expected_length ) ) {
			return null;
		}

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			wfMessage( "mwunit-assert-failure-has-length", $actual_length, $expected_length )->plain();

		return $actual_length === (int)$expected_length;
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}
}
