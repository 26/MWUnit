<?php

namespace MWUnit\Assertion;

class PageExists implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$page_name = trim( $frame->expand( $args[0] ) );
		$failure_message = isset( $args[1] ) ?
			trim( $frame->expand( $args[1] ) ) :
			wfMessage( "mwunit-assert-failure-page-exists" )->plain();

		$title = \Title::newFromText( $page_name );

		return $title !== null &&
			$title !== false &&
			$title->exists();
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}
}
