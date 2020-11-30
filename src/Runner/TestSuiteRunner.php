<?php

namespace MWUnit\Runner;

use Exception;
use MediaWiki\MediaWikiServices;
use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use MWUnit\ParserFunction\ParserMockParserFunction;
use MWUnit\TemplateMockStore;
use MWUnit\TestClass;
use MWUnit\TestRunStore;
use MWUnit\TestSuite;
use Title;
use WikiPage;

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
	 * UnitTestRunner constructor.
	 *
	 * @param TestSuite $test_suite The TestSuite to run
	 * @param TestRunStore $test_run_store
	 * @param callable|null $callback Callback function that gets called after every completed test
	 */
	public function __construct( TestSuite $test_suite, TestRunStore $test_run_store, callable $callback = null ) {
		$this->test_suite       = $test_suite;
		$this->test_run_store   = $test_run_store;
		$this->callback         = $callback;
	}

	/**
	 * Runs all tests in the group specified in the constructor.
	 *
	 * @throws MWUnitException
	 */
	public function run() {
		if ( !\Hooks::run( 'MWUnitBeforeFirstTest', [ &$pages ] ) ) {
			return;
		}

		foreach ( $this->test_suite as $test_class ) {
			$this->runTestClass( $test_class );
			$this->cleanupAfterFixture( $test_class->getTitle() );
		}

		try {
			\Hooks::run( 'MWUnitAfterTests', [ &$this->test_run_store ] );
		} catch ( Exception $e ) {
			throw new MWUnitException( 'mwunit-generic-error-description' );
		}
	}

	/**
	 * Returns the callback function called after each test result.
	 *
	 * @return callable
	 */
	public function getCallback() {
		return $this->callback;
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
	 * Called after having run the tests on a page.
	 *
	 * @param $page Title The article ID of the page
	 * @return bool Returns false on failure, true otherwise
	 * @throws MWUnitException
	 */
	private function cleanupAfterFixture( Title $page ) {
		try {
			\Hooks::run( 'MWUnitCleanupAfterPage', [ $page ] );
		} catch ( Exception $e ) {
			return false;
		}

		TemplateMockStore::getInstance()->reset();
		ParserMockParserFunction::restoreAndReset();

		return true;
	}

	/**
	 * Runs the given test class.
	 *
	 * @param TestClass $test_class
	 * @throws MWUnitException
	 */
	private function runTestClass( TestClass $test_class ) {
		$parser = MediaWikiServices::getInstance()->getParser();
		$parser = $parser->getFreshParser();

		$title = $test_class->getTitle();

		try {
			$wiki_page = WikiPage::factory( $title );
		} catch ( Exception $e ) {
			MWUnit::getLogger()->error( "Unable to create WikiPage object." );
			throw new MWUnitException();
		}

		$parser_options = $wiki_page->makeParserOptions( "canonical" );

		// Run the "setup" tag
		$parser->parse( $test_class->getSetUp(), $title, $parser_options );

		// Run each test case
		foreach ( $test_class->getTestCases() as $test_case ) {
		    $result = \Hooks::run( "MWUnitBeforeInitializeBaseTestRunner", [ &$test_case, &$test_class ] );

            if ( $result === false ) {
                // The hook returned false; skip this test
                continue;
            }

			$runner = new BaseTestRunner( $test_case );
			$result = $runner->run();

			$this->total_assertions_count += $result->getAssertionCount();
			$this->test_count += 1;

			if ( isset( $this->callback ) ) {
				call_user_func( $this->callback, $result->getResult() );
			}

			$this->test_run_store->append( $result );
		}

		// Run the "teardown" tag
		$parser->parse( $test_class->getTearDown(), $title, $parser_options );
	}
}
