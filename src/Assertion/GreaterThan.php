<?php

namespace MWUnit\Assertion;

class GreaterThan implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "greater_than";
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
		return 2;
	}

	/**
	 * Returns false if and only if $left is not greater than $right.
	 *
	 * @param string $failure_message
	 * @param string $left
	 * @param string $right
	 * @param string|null $message
	 * @return bool|null
	 */
	public static function assert( &$failure_message, $left, $right, $message = null ) {
		if ( !is_numeric( $left ) || !is_numeric( $right ) ) {
			$failure_message = wfMessage( 'mwunit-invalid-assertion' )->plain();
			return null;
		}

		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-greater-than", $left, $right )->plain();

		return (float)$left > (float)$right;
	}
}
