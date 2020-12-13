<?php

namespace MWUnit\Maintenance;

/**
 * Interface CommandLineResultPrinter
 * @package MWUnit\Maintenance
 */
interface CommandLineResultPrinter {
	/**
	 * Gets called for each completed test.
	 *
	 * @param \MWUnit\Runner\Result\TestResult $result The associated TestResult object
	 * @return void
	 */
	public function testCompletionCallback( \MWUnit\Runner\Result\TestResult $result );

	/**
	 * Outputs the given test to the console.
	 *
	 * @param \MWUnit\Runner\TestSuiteRunner $runner The associated UnitTestRunner object
	 * @return void
	 */
	public function outputTestResults( \MWUnit\Runner\TestSuiteRunner $runner );
}
