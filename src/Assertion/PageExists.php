<?php

namespace MWUnit\Assertion;

class PageExists implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "page_exists";
	}

	/**
	 * @inheritDoc
	 */
	public static function shouldRegister(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}

	/**
	 * Returns false if and only if the page specified by $page_name does not exist. $page_name must include the
	 * namespace prefix if the page is not located in the main namespace.
	 *
	 * @param string &$failure_message
	 * @param string $page_name
	 * @param string|null $message
	 * @return bool
	 */
	public static function assert( &$failure_message, $page_name, $message = null ) {
		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-page-exists" )->plain();

		$title = \Title::newFromText( $page_name );

		return $title !== null &&
			$title !== false &&
			$title->exists();
	}
}
