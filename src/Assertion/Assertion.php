<?php

namespace MWUnit\Assertion;

/**
 * Interface Assertion
 *
 * @package MWUnit\Assertion
 */
interface Assertion {
	/**
	 * Returns the name of this assertion as used in the parser function
	 * magic word, without the `assert_` prefix.
	 *
	 * @return string
	 */
	public static function getName(): string;

	/**
	 * Returns true if and only if this assertion should be registered with
	 * the Parser.
	 *
	 * @return bool
	 */
	public static function shouldRegister(): bool;

	/**
	 * Returns the minimum number of arguments required for this assertion.
	 *
	 * @return int
	 */
	public static function getRequiredArgumentCount(): int;
}
