<?php

namespace MWUnit\Assertion;

class StringContains implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$needle = trim( $frame->expand( $args[0] ) );
		$haystack = trim( $frame->expand( $args[1] ) );

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			wfMessage( "mwunit-assert-failure-contains-string", $needle, $haystack )->plain();

		return strpos( $haystack, $needle ) !== false;
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}
}
