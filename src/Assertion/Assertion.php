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
	 * @return void
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args );
}
