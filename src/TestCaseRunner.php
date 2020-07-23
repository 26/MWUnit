<?php

namespace MWUnit;

/**
 * Class TestCaseRunner
 * @package MWUnit
 */
class TestCaseRunner {
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
	 * Runs the given TestCase.
	 *
	 * @throws Exception\MWUnitException
	 * @throws \FatalError
	 * @throws \MWException
	 */
	public function run() {
		if ( !array_key_exists(
			MWUnit::getCanonicalTestNameFromTestCase( $this->test_case ),
			UnitTestRunner::$tests ) ) {
			// This test is not in the list of tests to run.
			return;
		}

		$run = new TestCaseRun( $this->test_case );
		$run->runTest();

		UnitTestRunner::$total_assertions_count += $run->getAssertionCount();
		UnitTestRunner::$total_test_count += 1;

		if ( !$run->getTestResult()->isTestRisky() && $run->getAssertionCount() === 0 ) {
			$run::$test_result->setRisky( wfMessage( 'mwunit-no-assertions' )->plain() );
		}

		\Hooks::run( 'MWUnitAfterTestComplete', [ &$run ] );

		UnitTestRunner::$test_results[] = $run->getTestResult();

		if ( is_callable( UnitTestRunner::$callback ) ) {
			$callable = UnitTestRunner::$callback;
			$callable( $run->getTestResult() );
		}
	}
}
