<?php

namespace MWUnit\Exception;

use Exception;

class InvalidTestPageException extends Exception {
	/**
	 * @var array
	 */
	private $errors;

	public function __construct( array $errors ) {
		$this->errors = $errors;

		parent::__construct( implode( "\n\n", $errors ) );
	}

	public function getErrors(): array {
		return $this->errors;
	}
}
