<?php

namespace MWUnit\Assertion;

/**
 * Class StandardAssertion
 *
 * Assertions that extends this class always get loaded.
 *
 * @package MWUnit\Assertion
 */
abstract class StandardAssertion implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function shouldRegister(): bool {
		return true;
	}
}
