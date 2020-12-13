<?php

namespace MWUnit\Assertion\Expressions;

use Expressions\Evaluator;
use Expressions\ExpressionException;
use Expressions\Parser;

class Expression extends ExpressionsAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "expression";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 1;
	}

	/**
	 * Returns false if and only if the given expression evaluates to false.
	 *
	 * @param string &$failure_message
	 * @param string $expression
	 * @param string|null $message
	 * @return bool|null
	 */
	public static function assert( string &$failure_message, string $expression, $message = null ) {
		$failure_message = $message ??
			wfMessage( "mwunit-assert-failure-expression", $expression )->plain();

		try {
			$parser = new Parser( $expression );
			$expression = $parser->parse();

			return Evaluator::evaluate( $expression );
		} catch ( ExpressionException $e ) {
			$failure_message = wfMessage( "mwunit-invalid-expression" );
			return null;
		}
	}
}
