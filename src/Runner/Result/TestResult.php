<?php

namespace MWUnit\Runner\Result;

use MWUnit\DatabaseTestCase;

/**
 * Class TestResult
 *
 * @package MWUnit\Runner\Result
 */
abstract class TestResult {
    const T_FAILED  = 0; /* phpcs:ignore */
    const T_RISKY   = 1; /* phpcs:ignore */
	const T_SUCCESS = 2; /* phpcs:ignore */

	/**
	 * @var DatabaseTestCase
	 */
	private $test_case;

    /**
     * TestResult constructor.
     *
     * @param DatabaseTestCase $case
     */
	public function __construct(DatabaseTestCase $case ) {
        $this->test_case = $case;
	}

	/**
	 * Returns the test case associated with this TestResult.
	 *
	 * @return DatabaseTestCase
	 */
	public function getTestCase(): DatabaseTestCase {
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
