<?php

namespace MWUnit\Assertion;

class StringContainsIgnoreCase implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$needle = trim( $frame->expand( $args[0] ) );
		$haystack = trim( $frame->expand( $args[1] ) );

		$needle_lower = strtolower( $needle );
		$haystack_lower = strtolower( $haystack );

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			wfMessage( "mwunit-assert-failure-contains-string", $needle_lower, $haystack_lower )->plain();

		return strpos( $haystack_lower, $needle_lower ) !== false;
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}
}
