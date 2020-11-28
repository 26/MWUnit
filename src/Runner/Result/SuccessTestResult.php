<?php

namespace MWUnit\Runner\Result;

/**
 * Class SuccessTestResult
 *
 * @package MWUnit\Runner\Result
 */
class SuccessTestResult extends TestResult {
	/**
	 * @inheritDoc
	 */
	public function toString(): string {
		return ".";
	}

	/**
	 * @inheritDoc
	 */
	public function getResultConstant(): int {
		return self::T_SUCCESS;
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage(): string {
		return "";
	}
}
