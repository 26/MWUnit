<?php

namespace MWUnit\Exception;

/**
 * Class MWUnitException
 * @package MWUnit\Exception
 */
class MWUnitException extends \Exception {
	public $message_name;
	public $arguments;

	/**
	 * MWUnitException constructor.
	 *
	 * @param string $message_name The message key for this exception
	 * @param array $arguments Arguments given to $message_name
	 */
	public function __construct( string $message_name = "", array $arguments = [] ) {
		$this->message_name = $message_name;
		$this->arguments = $arguments;

		$message = $message_name ?: wfMessage( $message_name, ...$arguments )->parse();

		parent::__construct( $message, 4500 );
	}
}
