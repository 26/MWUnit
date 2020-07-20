<?php

namespace MWUnit\Assertion;

class EqualsIgnoreCase implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$expected = trim( $frame->expand( $args[0] ) );
		$actual = trim( $frame->expand( $args[1] ) );

		$expected_lower = strtolower( $expected );
		$actual_lower = strtolower( $actual );

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			sprintf(
				wfMessage( "mwunit-assert-failure-equal" )->plain() . "\n\n%s",
				Equals::createDiff( $expected, $actual )
			);

		return $expected_lower === $actual_lower;
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}
}
