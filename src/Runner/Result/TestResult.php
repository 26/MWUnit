<?php

namespace MWUnit\Runner\Result;

use MWUnit\TestCase;

/**
 * Class TestResult
 *
 * @package MWUnit\Runner\Result
 */
abstract class TestResult {
	const T_SUCCESS = 0; /* phpcs:ignore */
	const T_FAILED  = 1; /* phpcs:ignore */
	const T_RISKY   = 2; /* phpcs:ignore */

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
     * Returns the page name this test is on or false on failure.
     *
     * @return string|bool
     */
    public function getPageName(): string {
        return $this->getTestCase()->getTitle()->getFullText();
    }

    /**
     * Returns the name of this test.
     *
     * @return string
     */
    public function getTestName(): string {
        return $this->test_case->getName();
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
	abstract public function getResult(): int;

    /**
     * Returns the message describing why the test did not succeed.
     *
     * @return string
     */
	abstract public function getMessage(): string;
}
