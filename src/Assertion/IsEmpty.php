<?php

namespace MWUnit\Assertion;

class IsEmpty implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$haystack = trim( $frame->expand( $args[0] ) );
		$failure_message = isset( $args[1] ) ?
			trim( $frame->expand( $args[1] ) ) :
				wfMessage( "mwunit-assert-failure-empty", $haystack )->plain();

		return empty( $haystack );
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}
}
