<?php

namespace MWUnit\Maintenance;

use MWUnit\TestResult;

require_once "CommandLineResultPrinter.php";

/**
 * Class TestDoxResultPrinter
 * @package MWUnit\Maintenance
 */
class TestDoxResultPrinter implements CommandLineResultPrinter {
	public $current_testsuite;

	/**
	 * @inheritDoc
	 */
	public function testCompletionCallback( TestResult $result ) {
		$testsuite = $result->getPageName();
		if ( !isset( $this->current_testsuite ) || $this->current_testsuite !== $testsuite ) {
			$this->current_testsuite = $testsuite;
			$title = \Title::newFromText( $testsuite );

			if ( $title === null || $title === false ) return;

			print ( "\n" . $title->getText() . "\n" );
		}

		switch ( $result->getResult() ) {
			case TestResult::T_SUCCESS:
				print( "  \033[0;32m✔\033[0m " . $this->toSentence( $result->getTestName() ) . "\n" );
				break;
			case TestResult::T_RISKY:
				print( "  \e[0;33m✘\e[0m " . $this->toSentence( $result->getTestName() ) . "\n" );
				$this->printFailureReason( $result->getRiskyMessage() );
				break;
			case TestResult::T_FAILED:
				print( "  \e[0;31m✘\e[0m " . $this->toSentence( $result->getTestName() ) . "\n" );
				foreach ( $result->getAssertionResults() as $result ) {
					if ( $result[ 'predicate_result' ] === false ) {
						$this->printFailureReason( $result[ 'failure_message' ] );
						break;
					}
				}
				break;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function outputTestResults( \MWUnit\UnitTestRunner $runner ) {
		$no_tests 		= $runner->getTotalTestCount();
		$no_assertions	= $runner->getTotalAssertionsCount();
		$no_not_passed 	= $runner->getNotPassedCount();

		if ( $runner->getNotPassedCount() > 0 ) {
			print( "\n\033[41mFAILURES!\e[0m\n\e[41mTests: $no_tests, Assertions: $no_assertions, Failures: $no_not_passed.\033[0m\n" );
			exit( 1 );
		} else {
			print( "\nOK ($no_tests tests, $no_assertions assertions)\n" );
			exit( 0 );
		}
	}

	/**
	 * Converts the given test name in camel case or snake case to a sentence.
	 *
	 * @param string $test_name
	 * @return string
	 */
	private function toSentence( string $test_name ): string {
		$parts = preg_split( '/(?=[A-Z_\-])/', $test_name, -1, PREG_SPLIT_NO_EMPTY );
		$parts = array_map( function ( $part ): string {
			return ucfirst( trim( $part, '_- ' ) );
		}, $parts );

		return implode( " ", $parts );
	}

	private function printFailureReason( string $failure_message ) {
		$lines = explode( "\n", $failure_message );
		$lines_padded = array_map( function( string $line ): string {
			return "    │  $line";
		}, $lines );

		print( "    │\n");
		print( implode( "\n", $lines_padded ) . "\n" );
		print( "    │\n\n");
	}
}