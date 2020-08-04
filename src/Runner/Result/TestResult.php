<?php

namespace MWUnit\Runner\Result;

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
	 * @var string
	 */
	private $testname;

	/**
	 * @var int
	 */
	private $assertion_count;

    /**
     * TestResult constructor.
     *
     * @param string $testname The canonical name of this test case
     * @param int $assertion_count The number of assertions used in this test case
     */
	public function __construct( string $testname, int $assertion_count ) {
        $this->testname         = $testname;
	    $this->assertion_count  = $assertion_count;
	}

	/**
	 * Returns the canonical test name of this object.
	 *
	 * @return string
	 */
	public function getCanonicalTestName(): string {
		return $this->testname;
	}

	/**
	 * Returns the number of assertions in this test case.
	 *
	 * @return int
	 */
	public function getAssertionCount(): int {
		return $this->assertion_count;
	}

    /**
     * Returns the page name this test is on or false on failure.
     *
     * @return string|bool
     */
    public function getPageName(): string {
        $page_text = explode( "::", $this->testname )[0];
        $title_object = \Title::newFromText( $page_text, NS_TEST );

        if ( !$title_object instanceof \Title ) {
            return false;
        }

        return $title_object->getFullText();
    }

    /**
     * Returns the name of this test.
     *
     * @return string
     */
    public function getTestName(): string {
        return explode( "::", $this->testname )[1];
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
