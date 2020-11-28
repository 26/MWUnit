<?php

namespace MWUnit\Runner;

use FatalError;
use MWException;
use MWUnit\Exception;
use MWUnit\MWUnit;
use MWUnit\Runner\Result\TestResult;
use MWUnit\TestCase;

/**
 * Class BaseTestRunner
 *
 * This class handles the initialisation of a TestRun object.
 *
 * @package MWUnit
 */
class BaseTestRunner {
	/**
	 * @var TestCase The test case
	 */
	private $test_case;

	/**
	 * TestCaseRunner constructor.
	 * @param TestCase $test_case
	 */
	public function __construct( TestCase $test_case ) {
		$this->test_case = $test_case;
	}

	/**
	 * Runs the TestCase.
	 *
	 * @throws Exception\MWUnitException
	 * @throws FatalError
	 * @throws MWException
	 * @return TestRun
	 */
	public function run(): TestRun {
		MWUnit::getLogger()->debug( "Running test case {testcase}", [
			'testcase' => $this->test_case->getCanonicalName()
		] );

		$run = new TestRun( $this->test_case );
		$run->runTest();

		\Hooks::run( 'MWUnitAfterTestComplete', [ &$run ] );

		$this->doAssertionCheck( $run );

		return $run;
	}

	/**
	 * Checks whether or not the test case performed any assertions and marks it as "Risky" when
	 * no assertions were performed.
	 * @param TestRun $run
	 */
	private function doAssertionCheck( TestRun $run ) {
		$test_result = $run->getResult();

		if ( $test_result->getResultConstant() === TestResult::T_RISKY ) {
			// Do not overwrite the result of tests already marked as risky
			return;
		}

		if ( $this->test_case->getAttribute( 'doesnotperformassertions' ) !== false ) {
			// This test is explicitly marked as not performing any assertions
			return;
		}

		if ( $run->getAssertionCount() === 0 ) {
			$run->setRisky( wfMessage( 'mwunit-no-assertions' )->parse() );
		}
	}
}
