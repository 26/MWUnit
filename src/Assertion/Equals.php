<?php

namespace MWUnit\Assertion;

class Equals extends StandardAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "equals";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}

	/**
	 * Returns false if and only if the two variables, $expected and $actual are not identical to each
	 * other.
	 *
	 * @param string &$failure_message
	 * @param string $expected
	 * @param string $actual
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( string &$failure_message, string $expected, string $actual, $message = null ) {
		$failure_message = $message ??
			sprintf(
				wfMessage( "mwunit-assert-failure-equal" )->plain() . "\n\n%s",
				self::createDiff( $expected, $actual )
			);

		return $expected === $actual;
	}

	/**
	 * Creates a very simple "diff" view of what was expected and what the actual value of the thing that got
	 * compared was.
	 *
	 * @param string $expected
	 * @param string $actual
	 * @return string
	 */
	public static function createDiff( string $expected, string $actual ): string {
		$expected_lines = explode( "\n", $expected );
		$actual_lines   = explode( "\n", $actual );

		$expected_formatted = implode( "\n", array_map( function ( $line ): string {
			return "- $line";
		}, $expected_lines ) );

		$actual_formatted = implode( "\n", array_map( function ( $line ): string {
			return "+ $line";
		}, $actual_lines ) );

		return sprintf( "--- " . wfMessage( "mwunit-expected" )->plain() .
			"\n+++ " . wfMessage( "mwunit-actual" )->plain() .
			"\n@@ @@\n$expected_formatted\n$actual_formatted" );
	}
}
