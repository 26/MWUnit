<?php

namespace MWUnit\Maintenance;

use MWUnit\Runner\Result\TestResult;

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

		if ( $testsuite === false ) {
			return;
		}

		if ( !isset( $this->current_testsuite ) || $this->current_testsuite !== $testsuite ) {
			$this->current_testsuite = $testsuite;
			$title = \Title::newFromText( $testsuite );

			if ( $title === null || $title === false ) {
				return;
			}

			print ( $title->getText() . "\n" );
		}

		switch ( $result->getResult() ) {
			case TestResult::T_SUCCESS:
				print( "  \033[0;32m✔\033[0m " . \MWUnit\MWUnit::testNameToSentence( $result->getTestName() ) . "\n" );
				break;
			case TestResult::T_RISKY:
				print( "  \e[0;33m✘\e[0m " . \MWUnit\MWUnit::testNameToSentence( $result->getTestName() ) . "\n" );
				$this->printFailureReason( $result->getMessage() );
				break;
			case TestResult::T_FAILED:
				print( "  \e[0;31m✘\e[0m " . \MWUnit\MWUnit::testNameToSentence( $result->getTestName() ) . "\n" );
				$this->printFailureReason( $result->getMessage() );
				break;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function outputTestResults(\MWUnit\Runner\TestSuiteRunner $runner ) {
		$no_tests 		= $runner->getTestCount();
		$no_assertions	= $runner->getTotalAssertionsCount();
		$no_not_passed 	= $runner->getNotPassedCount();

		if ( $runner->getNotPassedCount() > 0 ) {
			print( "\n\033[41mFAILURES!\e[0m\n\e[41mTests: $no_tests, " .
				"Assertions: $no_assertions, " .
				"Failures: $no_not_passed.\033[0m\n" );
		} else {
			print( "\nOK ($no_tests tests, $no_assertions assertions)\n" );
		}
	}

	/**
	 * Prints the failure message in TextDox format.
	 *
	 * @param string $failure_message
	 */
	private function printFailureReason( string $failure_message ) {
		$lines = explode( "\n", $failure_message );
		$lines_padded = array_map( function ( string $line ): string {
			return "    │  $line";
		}, $lines );

		print( "    │\n" );
		print( implode( "\n", $lines_padded ) . "\n" );
		print( "    │\n\n" );
	}
}
