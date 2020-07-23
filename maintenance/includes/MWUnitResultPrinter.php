<?php

namespace MWUnit\Maintenance;

use MWUnit\TestResult;

require_once "CommandLineResultPrinter.php";

/**
 * Class MWUnitResultPrinter
 * @package MWUnit\Maintenance
 */
class MWUnitResultPrinter implements CommandLineResultPrinter {
	/**
	 * @var int
	 */
	protected $result_output_columns;

	/**
	 * @var int The output column for the next result (used for display purposes only)
	 */
	protected $result_output_column = 0;

	/**
	 * @var int Preceding numeric identifier for each risky/failed test (used for display purposes only)
	 */
	private $count = 0;

	/**
	 * @var bool
	 */
	private $no_progress = false;

	/**
	 * MWUnitResultPrinter constructor.
	 * @param int $result_output_columns
	 * @param bool $no_progress
	 */
	public function __construct( int $result_output_columns, bool $no_progress ) {
		$this->result_output_columns = $result_output_columns;
		$this->no_progress = $no_progress;
	}

	/**
	 * @inheritDoc
	 */
	public function testCompletionCallback( TestResult $result ) {
		if ( $this->no_progress ) {
			return;
		}

		print( $result->toString() );
		$this->result_output_column++;

		if ( $this->result_output_column >= $this->result_output_columns ) {
			$this->result_output_column = 0;
			print( "\n" );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function outputTestResults( \MWUnit\UnitTestRunner $runner ) {
		$no_tests 		= $runner->getTotalTestCount();
		$no_assertions	= $runner->getTotalAssertionsCount();
		$no_not_passed 	= $runner->getNotPassedCount();

		print( "\n\n" );

		if ( $no_not_passed === 0 ) {
			print( "OK ($no_tests tests, $no_assertions assertions)\n" );
			exit( 0 );
		}

		$failed_tests = $runner->getFailedTests();
		$risky_tests = $runner->getRiskyTests();

		$failed_count = count( $failed_tests );
		$risky_count = count( $risky_tests );

		$this->count = 1;

		if ( $failed_count > 0 ) {
			$failed_count === 1 ?
				print( "There was 1 failure:" ) :
				print( "There were $failed_count failures:" );

			print( "\n\n" );

			foreach ( $failed_tests as $test ) { $this->printTest( $test );
			}
		}

		$this->count = 1;

		if ( $risky_count > 0 ) {
			$risky_count === 1 ?
				print( "There was 1 test considered risky:" ) :
				print( "There were $risky_count tests considered risky:" );

			print( "\n\n" );

			foreach ( $risky_tests as $test ) { $this->printTest( $test );
			}
		}

		print( "\033[41mFAILURES!\e[0m\n\e[41mTests: $no_tests, " .
			"Assertions: $no_assertions, " .
			"Failures: $no_not_passed.\033[0m\n" );

		exit( 1 );
	}

	/**
	 * Prints the given test result object to the console.
	 *
	 * @param TestResult $test
	 */
	private function printTest( TestResult $test ) {
		print( $this->count . ") " );
		print( $test->getCanonicalTestName() . "\n" );

		if ( $test->getResult() === TestResult::T_FAILED ) {
			print( $test->getFailureMessage() );
		} elseif ( $test->getResult() === TestResult::T_RISKY ) {
			print( $test->getRiskyMessage() );
		}

		print( "\n\n" );
		$this->count++;
	}
}
