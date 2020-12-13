<?php

namespace MWUnit\Runner\Result;

use MWUnit\TestCase;

/**
 * Class TestResult
 *
 * @package MWUnit\Runner\Result
 */
abstract class TestResult {
    const T_FAILED  = 0; /* phpcs:ignore */
    const T_RISKY   = 1; /* phpcs:ignore */
	const T_SUCCESS = 2; /* phpcs:ignore */
    const T_SKIPPED = 3; /* phpcs:ignore */

	/**
	 * @var TestCase
	 */
	private $test_case;

	/**
	 * TestResult constructor.
	 *
	 * @param TestCase $case
	 */
	public function __construct( TestCase $case ) {
		$this->test_case = $case;
	}

	/**
	 * Returns the test case associated with this TestResult.
	 *
	 * @return TestCase
	 */
	public function getTestCase(): TestCase {
		return $this->test_case;
	}

	/**
	 * Returns the string variant of this test result.
	 *
	 * @return string
	 */
	abstract public function toString(): string;

	/**
	 * Returns the result of the test; either T_SUCCESS, T_FAILED or T_RISKY.
	 *
	 * @return int
	 */
	abstract public function getResultConstant(): int;

	/**
	 * Returns the message describing why the test did not succeed.
	 *
	 * @return string
	 */
	abstract public function getMessage(): string;
}
