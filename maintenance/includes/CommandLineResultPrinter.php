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
	 * @param \MWUnit\TestResult $result The associated TestResult object
	 * @return void
	 */
	public function testCompletionCallback( \MWUnit\TestResult $result );

	/**
	 * Outputs the given test to the console.
	 *
	 * @param \MWUnit\UnitTestRunner $runner The associated UnitTestRunner object
	 * @return void
	 */
	public function outputTestResults( \MWUnit\UnitTestRunner $runner );
}