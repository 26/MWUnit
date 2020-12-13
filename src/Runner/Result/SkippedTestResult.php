<?php

namespace MWUnit\Runner\Result;

use MWUnit\TestCase;

class SkippedTestResult extends TestResult {
	/**
	 * @var string
	 */
	private $message;

	/**
	 * SkippedTestResult constructor.
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
		return "\033[43mS\033[0m";
	}

	/**
	 * @inheritDoc
	 */
	public function getResultConstant(): int {
		return self::T_SKIPPED;
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage(): string {
		return $this->message;
	}
}
