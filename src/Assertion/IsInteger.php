<?php

namespace MWUnit\Assertion;

class IsInteger implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$haystack = trim( $frame->expand( $args[0] ) );

		$failure_message = isset( $args[1] ) ?
			trim( $frame->expand( $args[1] ) ) :
			wfMessage( "mwunit-assert-failure-is-integer", $haystack )->plain();

		return preg_match( "/^\-?[0-9]+$/", $haystack );
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}
}
