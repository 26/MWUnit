<?php

namespace MWUnit\Runner\Result;

use MWUnit\TestCase;

/**
 * Class RiskyTestResult
 *
 * @package MWUnit\Runner\Result
 */
class RiskyTestResult extends TestResult {
	/**
	 * @var string
	 */
	private $message;

	/**
	 * RiskyTestResult constructor.
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
		return "\033[103mR\033[0m";
	}

	/**
	 * @inheritDoc
	 */
	public function getResultConstant(): int {
		return self::T_RISKY;
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage(): string {
		return $this->message;
	}
}
