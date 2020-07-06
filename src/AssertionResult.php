<?php

namespace MWUnit;

class AssertionResult {
	/**
	 * @var bool
	 */
	public $predicate_result;

	/**
	 * @var string|null
	 */
	public $failure_message;

	/**
	 * AssertionResult constructor.
	 *
	 * @param bool $predicate_result The result of the assertion
	 * @param string|null $failure_message The failure message (NULL if the predicate evaluated to "true")
	 */
	public function __construct(
		bool $predicate_result,
		$failure_message = null ) {
		$this->predicate_result = $predicate_result;
		$this->failure_message = $failure_message;
	}
}
