<?php

namespace MWUnit\Assertion;

class That implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$that = trim( $frame->expand( $args[0] ) );

		$failure_message = isset( $args[1] ) ?
			trim( $frame->expand( $args[1] ) ) :
			wfMessage( "mwunit-assert-failure-that", $that )->plain();

		return filter_var( $that, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}
}
