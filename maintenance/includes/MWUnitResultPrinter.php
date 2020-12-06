<?php

namespace MWUnit\Maintenance;

use MWUnit\Exception\MWUnitException;
use MWUnit\Runner\Result\TestResult;
use MWUnit\Runner\TestRun;
use MWUnit\Runner\TestSuiteRunner;

require_once "CommandLineResultPrinter.php";

/**
 * Class MWUnitResultPrinter
 * @package MWUnit\Maintenance
 */
class MWUnitResultPrinter implements CommandLineResultPrinter {
	// TODO: Localize this file

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
	 * @throws MWUnitException
	 */
	public function outputTestResults( TestSuiteRunner $runner ) {
		$no_tests 		= $runner->getTestCount();
		$no_assertions	= $runner->getTotalAssertionsCount();

		$no_risky    	= $runner->getRiskyCount();
		$no_failed      = $runner->getFailedCount();

		print( "\n\n" );

		if ( ( $no_risky + $no_failed ) === 0 ) {
			print( "OK ($no_tests tests, $no_assertions assertions)\n" );
			return;
		}

		$test_run_store = $runner->getTestRunStore();

		$failed_runs     = $test_run_store->getFailedRuns();
		$risky_runs      = $test_run_store->getRiskyRuns();
		$successful_runs = $test_run_store->getStoreWithResult( TestResult::T_SUCCESS );

		$failed_count = $test_run_store->getFailedCount();
		$risky_count = $test_run_store->getRiskyCount();
		$skipped_count = $test_run_store->getSkippedCount();

		$this->count = 1;
		foreach ( $successful_runs->getAll() as $run ) {
			$this->printRun( $run );
		}

		print( "------\n\n" );

		$this->count = 1;
		if ( $failed_count > 0 ) {
			$failed_count === 1 ?
				print( "There was 1 failure:" ) :
				print( "There were $failed_count failures:" );

			print( "\n\n" );

			foreach ( $failed_runs->getAll() as $run ) {
				$this->printRun( $run );
			}
		}

		$this->count = 1;
		if ( $risky_count > 0 ) {
			$risky_count === 1 ?
				print( "There was 1 test considered risky:" ) :
				print( "There were $risky_count tests considered risky:" );

			print( "\n\n" );

			foreach ( $risky_runs->getAll() as $run ) {
				$this->printRun( $run );
			}
		}

		print( "\033[41mFAILURES!\e[0m\n\e[41mTests: $no_tests, " .
			"Assertions: $no_assertions, " .
			"Risky tests: $risky_count, " .
			"Failures: $failed_count, " .
			"Skipped: $skipped_count.\033[0m\n" );
	}

	/**
	 * Prints the given test run object to the console.
	 *
	 * @param TestRun $run
	 */
	private function printRun( TestRun $run ) {
		$test = $run->getResult();

		$message = $test->getMessage();
		$output  = $this->formatOutput(
			$run->getTestOutputs()
		);

		if ( $message || $output ) {
			print( $this->count . ") " );
			print( $test->getTestCase() . "\n" );

			if ( $message ) {
				print( $message );
			}

			if ( !$message && $output ) {
				print( "\nThis test outputted the following:\n$output" );
			} elseif ( $output ) {
				print( "\n\nIn addition, the test outputted the following:\n$output" );
			}

			print( "\n\n" );
			$this->count++;
		}
	}

	/**
	 * @param string[] $test_outputs
	 * @return string
	 */
	private function formatOutput( array $test_outputs ): string {
		return count( $test_outputs ) === 0 ?
			'' :
			implode( "\n", $test_outputs );
	}
}
