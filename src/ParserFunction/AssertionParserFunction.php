<?php

namespace MWUnit\ParserFunction;

use MWUnit\Assertion\Assertion;
use MWUnit\Injector\TestRunInjector;
use MWUnit\MWUnit;
use MWUnit\ParserData;
use MWUnit\Runner\TestRun;

class AssertionParserFunction implements ParserFunction, TestRunInjector {
    /**
     * @var TestRun
     */
    private static $run;

    /**
     * @var string
     */
    private $assertion;

    /**
     * AssertionParserFunction constructor.
     *
     * @param string $assertion
     */
    public function __construct( string $assertion ) {
        $this->assertion = $assertion;
    }

    /**
     * @inheritDoc
     */
    public static function setTestRun( TestRun $run ) {
        self::$run = $run;
    }

    /**
     * Creates a new AssertionParserFunction from the Assertion class name.
     *
     * @param string $assertion
     * @return AssertionParserFunction
     */
    public static function newFromClass( string $assertion ): self {
        return new self( $assertion );
    }

    /**
     * Gets called when the parser parses an assertion parser function. Handles the assertion
     * and reports the result to TestCaseRun when applicable.
     *
     * @param ParserData $data
     * @return string
     */
	public function execute( ParserData $data ) {
	    if ( !self::$run ) {
		    return '';
        }

	    // Short-circuit if the result is already available
		if ( self::$run->resultAvailable() ) {
		    return '';
		}

		// Wow, much assertion
		assert( $this->assertion instanceof Assertion );

		$required_arg_count = $this->assertion::getRequiredArgumentCount();
		$actual_arg_count 	= $data->count();
		$argument_range 	= range( $required_arg_count, $required_arg_count + 1 );

		if ( !in_array( $actual_arg_count, $argument_range ) ) {
            self::$run->setRisky( wfMessage( 'mwunit-invalid-assertion' )->parse() );
			return '';
		}

		$this->callAssertion( $data );

		return '';
	}

    /**
     * Calls the "assert" method on the given class with the given arguments and handles
     * the response. This function also adds the assertion result to the TestCaseRun object.
     *
     * @param ParserData $data
     * @return null|bool Returns the result of the assertion, or null on failure
     */
	private function callAssertion( ParserData $data ) {
		$failure_message = '';
		$result = $this->assertion::assert( $failure_message, ...$data->getArguments() );

        if ( $result === null ) {
            self::$run->setRisky( $failure_message );
        } else if ( $result === false ) {
            self::$run->setFailure( $failure_message );
        }

        self::$run->incrementAssertionCount();

		return $result;
	}
}
