<?php

namespace MWUnit;

class TestResult {
	const T_SUCCESS = 0; /* phpcs:ignore */
	const T_FAILED  = 1; /* phpcs:ignore */
	const T_RISKY   = 2; /* phpcs:ignore */

	/**
	 * @var int
	 */
	private $test_result = self::T_SUCCESS;

	/**
	 * @var string
	 */
	private $canonical_testname;

	/**
	 * @var string
	 */
	private $risky_message = '';

	/**
	 * @var string
	 */
	private $failure_message = '';

	/**
	 * @var bool
	 */
	private $covers = false;

	/**
	 * @var int
	 */
	private $assertion_count = 0;

	/**
	 * TestResult constructor.
	 * @param string $canonical_testname
	 */
	public function __construct( string $canonical_testname ) {
		$this->canonical_testname = $canonical_testname;
	}

	/**
	 * Increments the count of assertions for this test case.
	 */
	public function incrementAssertionCount() {
		$this->assertion_count++;
	}

	/**
	 * Sets the test result to "FAILED" and sets the message for the "failed" message.
	 *
	 * @param string $message
	 */
	public function setFailed( $message ) {
		$this->failure_message = $message;
		$this->test_result = self::T_FAILED;
	}

	/**
	 * Sets the test result to "RISKY" and sets the message for the "risky" message.
	 *
	 * @param string $message
	 */
	public function setRisky( $message ) {
		$this->risky_message = $message;
		$this->test_result = self::T_RISKY;
	}

	/**
	 * Returns true if and only if the test did not fail and was not marked as "RISKY".
	 *
	 * @return bool
	 */
	public function didTestSucceed(): bool {
		return $this->test_result === self::T_SUCCESS;
	}

	/**
	 * Returns true if and only if the test was marked as risky.
	 *
	 * @return bool
	 */
	public function isTestRisky(): bool {
		return $this->test_result === self::T_RISKY;
	}

	/**
	 * Returns the canonical test name of this object.
	 *
	 * @return string
	 */
	public function getCanonicalTestName(): string {
		return $this->canonical_testname;
	}

	/**
	 * Returns the "risky" message.
	 *
	 * @return string
	 */
	public function getRiskyMessage(): string {
		return $this->risky_message;
	}

	/**
	 * Returns the "failure" message.
	 *
	 * @return string
	 */
	public function getFailureMessage(): string {
		return $this->failure_message;
	}

	/**
	 * Returns the page name this test is on or false on failure.
	 *
	 * @return string|bool
	 */
	public function getPageName(): string {
		$page_text = explode( "::", $this->canonical_testname )[0];
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
		return explode( "::", $this->canonical_testname )[1];
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
	 * Returns the result constant for this test.
	 *
	 * @return int
	 */
	public function getResult(): int {
		return $this->test_result;
	}

	/**
	 * Sets the coverage report for the "covers" template to true.
	 */
	public function setTemplateCovered() {
		$this->covers = true;
	}

	/**
	 * Returns true if and only if the template in the "covers" annotation got used in this test case.
	 *
	 * @return bool
	 */
	public function isTemplateCovered() {
		return $this->covers;
	}

	/**
	 * Returns the string variant of this test result. Used in the CLI runner.
	 *
	 * @return string
	 */
	public function toString(): string {
		switch ( $this->test_result ) {
			case self::T_SUCCESS:
				return ".";
			case self::T_RISKY:
				return "\033[43mR\033[0m";
			default:
				return "\033[41mF\033[0m";
		}
	}
}
