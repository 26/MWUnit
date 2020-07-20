<?php

namespace MWUnit\Assertion;

class StringStartsWith implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$needle = trim( $frame->expand( $args[0] ) );
		$haystack = trim( $frame->expand( $args[1] ) );

		$needle_length = strlen( $needle );

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			wfMessage( "mwunit-assert-failure-string-starts-with", $needle, $haystack )->plain();

		return $needle_length <= strlen( $haystack ) &&
			substr( $haystack, 0, $needle_length ) === $needle;
	}


	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}
}
