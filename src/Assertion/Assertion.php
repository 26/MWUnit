<?php

namespace MWUnit\Assertion;

/**
 * Interface Assertion
 *
 * @package MWUnit\Assertion
 */
interface Assertion {
	/**
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 * @param array $args
	 * @param $failure_message
	 * @return void
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message );

	/**
	 * Returns the minimum number of arguments required for this assertion.
	 *
	 * @return int
	 */
	public static function getRequiredArgumentCount(): int;
}
