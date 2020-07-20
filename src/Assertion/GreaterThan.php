<?php

namespace MWUnit\Assertion;

class GreaterThan implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$a = trim( $frame->expand( $args[0] ) );
		$b = trim( $frame->expand( $args[1] ) );

		if ( !is_numeric( $a ) || !is_numeric( $b ) ) {
			$failure_message = wfMessage( 'mwunit-invalid-assertion' )->plain();

			return null;
		}

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			wfMessage( "mwunit-assert-failure-greater-than", $a, $b )->plain();

		return (float)$a > (float)$b;
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}
}
