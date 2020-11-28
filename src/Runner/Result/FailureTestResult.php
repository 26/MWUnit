<?php

namespace MWUnit\Runner\Result;

use MWUnit\TestCase;

/**
 * Class FailureTestResult
 *
 * @package MWUnit\Runner\Result
 */
class FailureTestResult extends TestResult {
	/**
	 * @var string
	 */
	private $message;

	/**
	 * FailureTestResult constructor.
	 *
	 * @param string $message
	 * @param TestCase $test_case
	 */
	public function __construct( string $message, TestCase $test_case ) {
		$this->message = $message;
		parent::__construct( $test_case );
	}

	/**
	 * @inheritDoc
	 */
	public function toString(): string {
		return "\033[41mF\033[0m";
	}

	/**
	 * @inheritDoc
	 */
	public function getResultConstant(): int {
	   return self::T_FAILED;
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage(): string {
		return $this->message;
	}
}
