<?php

namespace MWUnit\Maintenance;

use MWUnit\MWUnit;
use MWUnit\Runner\Result\TestResult;
use MWUnit\Runner\TestSuiteRunner;

require_once "CommandLineResultPrinter.php";

/**
 * Class TestDoxResultPrinter
 * @package MWUnit\Maintenance
 */
class TestDoxResultPrinter implements CommandLineResultPrinter {
	// TODO: Localize this file

	public $current_testsuite;

	/**
	 * @inheritDoc
	 */
	public function testCompletionCallback( TestResult $result ) {
		$title = $result->getTestCase()->getTestPage();
		$page = $title->getFullText();

		if ( !isset( $this->current_testsuite ) || $this->current_testsuite !== $page ) {
			$this->current_testsuite = $page;

			print ( $title->getText() . "\n" );
		}

		$test_case = $result->getTestCase();
		$test_name = $test_case->getName();

		$sentence = MWUnit::testNameToSentence( $test_name );

		switch ( $result->getResultConstant() ) {
			case TestResult::T_SUCCESS:
				print( "  \033[0;32m✔\033[0m " . $sentence . "\n" );
				break;
			case TestResult::T_RISKY:
				print( "  \e[0;33m✘\e[0m " . $sentence . "\n" );
				$this->printFailureReason( $result->getMessage() );
				break;
			case TestResult::T_SKIPPED:
				print( "  \e[0;37m✘\e[0m " . $sentence . "\n" );
				$this->printFailureReason( $result->getMessage() );
				break;
			case TestResult::T_FAILED:
				print( "  \e[0;31m✘\e[0m " . $sentence . "\n" );
				$this->printFailureReason( $result->getMessage() );
				break;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function outputTestResults( TestSuiteRunner $runner ) {
		$no_tests 		= $runner->getTestCount();
		$no_assertions	= $runner->getTotalAssertionsCount();

		$risky_count    = $runner->getRiskyCount();
		$failed_count 	= $runner->getFailedCount();

		if ( ( $risky_count + $failed_count ) > 0 ) {
			print( "\n\033[41mFAILURES!\e[0m\n\e[41mTests: $no_tests, " .
				"Assertions: $no_assertions, " .
				"Risky tests: $risky_count, " .
				"Failures: $failed_count.\033[0m\n" );
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
