<?php

namespace MWUnit;

/**
 * Class BaseTestRunner
 *
 * This class handles the initialisation of a TestRun and the communication with other classes
 * about the result of that TestRun.
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
	 * Runs the given TestCase.
	 *
	 * @throws Exception\MWUnitException
	 * @throws \FatalError
	 * @throws \MWException
	 * @throws \ConfigException
	 */
	public function run() {
		if ( !array_key_exists(
			MWUnit::getCanonicalTestNameFromTestCase( $this->test_case ),
			TestSuiteRunner::$tests ) ) {
			// This test is not in the list of tests to run.
			return;
		}

		MWUnit::getLogger()->debug( "Running test case {testcase}", [
			'testcase' => MWUnit::getCanonicalTestNameFromTestCase( $this->test_case )
		] );

		$run = new TestRun( $this->test_case );
		$run->runTest();

		TestSuiteRunner::$total_assertions_count += $run->getAssertionCount();
		TestSuiteRunner::$total_test_count += 1;

		if (
			$this->test_case->getOption( 'doesnotperformassertions' ) === false &&
			!$run->getTestResult()->isTestRisky() &&
			$run->getAssertionCount() === 0
		) {
			$run::$test_result->setRisky( wfMessage( 'mwunit-no-assertions' )->plain() );
		}

		\Hooks::run( 'MWUnitAfterTestComplete', [ &$run ] );

		TestSuiteRunner::$test_results[] = $run->getTestResult();

		if ( is_callable( TestSuiteRunner::$callback ) ) {
			MWUnit::getLogger()->debug( "Calling test case completion callback {callback}", [
				TestSuiteRunner::$callback
			] );

			$callable = TestSuiteRunner::$callback;
			$callable( $run->getTestResult() );
		}
	}
}
