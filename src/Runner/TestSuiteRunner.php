<?php

namespace MWUnit\Runner;

use Exception;
use MWUnit\MWUnit;
use MWUnit\TestClass;
use MWUnit\TestRunStore;
use MWUnit\TestSuite;

/**
 * Class TestSuiteRunner
 *
 * This class runs all tests given in the constructor of this class, regardless on
 * which page a test is located. It handles the running of tests and the collection
 * of the results of the tests it ran.
 *
 * @package MWUnit
 */
class TestSuiteRunner {
	/**
	 * @var bool
	 */
	public static $running = false;

	/**
	 * @var int The total number of assertions for the current run.
	 */
	private $total_assertions_count = 0;

	/**
	 * @var int The total number of tests for the current run.
	 */
	private $test_count = 0;

	/**
	 * @var callable
	 */
	private $callback;

	/**
	 * @var TestSuite
	 */
	private $test_suite;

	/**
	 * @var TestRunStore
	 */
	private $test_run_store;

	/**
	 * Creates a new TestSuiteRunner object from the given TestSuite.
	 *
	 * @param TestSuite $test_suite
	 * @param callable|null $callback Callback function that gets called after every completed test
	 *
	 * @return TestSuiteRunner
	 */
	public static function newFromTestSuite( TestSuite $test_suite, callable $callback = null ): TestSuiteRunner {
		return new self( $test_suite, new TestRunStore(), $callback );
	}

	/**
	 * TestSuiteRunner constructor.
	 *
	 * @param TestSuite $test_suite The TestSuite to run
	 * @param TestRunStore $test_run_store
	 * @param callable|null $callback Callback function that gets called after every completed test
	 */
	public function __construct( TestSuite $test_suite, TestRunStore $test_run_store, callable $callback = null ) {
		$this->test_suite       = $test_suite;
		$this->test_run_store   = $test_run_store;
		$this->callback         = $callback;

		self::$running = true;
	}

	/**
	 * Runs all tests in the group specified in the constructor.
	 */
	public function run() {
		try {
			if ( !\Hooks::run( 'MWUnitBeforeFirstTest', [ &$this->test_suite ] ) ) {
				return;
			}
		} catch ( \Exception $e ) {
			MWUnit::getLogger()->error(
				"Exception while running hook MWUnitBeforeFirstTest: {e}",
				[ "e" => $e->getMessage() ]
			);
		}

		$this->runTestClasses();

		try {
			\Hooks::run( 'MWUnitAfterTests', [ &$this->test_run_store ] );
		} catch ( Exception $e ) {
			MWUnit::getLogger()->error(
				"Exception while running hook MWUnitAfterTests: {e}",
				[ "e" => $e->getMessage() ]
			);
		}
	}

	/**
	 * Returns the test cases run in this Test Suite as a TestRunStore object.
	 *
	 * @return TestRunStore
	 */
	public function getTestRunStore(): TestRunStore {
		return $this->test_run_store;
	}

	/**
	 * Returns the total number of assertions for the current run.
	 *
	 * @return int The total number of assertions for the current run
	 */
	public function getTotalAssertionsCount(): int {
		return $this->total_assertions_count;
	}

	/**
	 * Returns the number of tests ran.
	 *
	 * @return int The total number of tests ran
	 */
	public function getTestCount(): int {
		return $this->test_count;
	}

	/**
	 * Returns the number of tests marked as risky.
	 *
	 * @return int
	 */
	public function getRiskyCount() {
		return $this->test_run_store->getRiskyCount();
	}

	/**
	 * Returns the number of failed tests.
	 *
	 * @return int
	 */
	public function getFailedCount() {
		return $this->test_run_store->getFailedCount();
	}

	/**
	 * Returns the number of skipped tests.
	 *
	 * @return int
	 */
	public function getSkippedCount() {
		return $this->test_run_store->getSkippedCount();
	}

	/**
	 * Runs all the test classes in this test suite.
	 */
	public function runTestClasses() {
		foreach ( $this->test_suite as $test_class ) {
			$this->runTestClass( $test_class );
		}
	}

	/**
	 * Runs the given test class.
	 *
	 * @param TestClass $test_class
	 */
	private function runTestClass( TestClass $test_class ) {
		$runner = new TestClassRunner( $test_class, $this->test_run_store, $this->callback );
		$runner->run();

		$this->total_assertions_count += $runner->getAssertionCount();
		$this->test_count             += $runner->getRunTestCount();
	}
}
