<?php

namespace MWUnit\Assertion\Expressions;

use MWUnit\Assertion\Assertion;

/**
 * Class ExpressionsAssertion
 *
 * Assertions extending this class only get loaded if Expressions is installed.
 *
 * @package MWUnit\Assertion\Expressions
 */
abstract class ExpressionsAssertion implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function shouldRegister(): bool {
		return \ExtensionRegistry::getInstance()->isLoaded( 'Expressions' );
	}
}
