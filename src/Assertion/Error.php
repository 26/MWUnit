<?php

namespace MWUnit\Assertion;

class Error implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$haystack = trim( $frame->expand( $args[0] ) );
		$failure_message = isset( $args[1] ) ?
			trim( $frame->expand( $args[1] ) ) :
			wfMessage( "mwunit-assert-failure-error" )->plain();

		return preg_match(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$haystack
		);
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}
}
