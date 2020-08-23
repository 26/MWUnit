<?php

namespace MWUnit\Runner;

use ContentHandler;
use MediaWiki\MediaWikiServices;
use MWException;
use MWUnit\ParserFunction\ParserMockParserFunction;
use MWUnit\ParserFunction\TestCaseParserFunction;
use MWUnit\Exception;
use MWUnit\MWUnit;
use MWUnit\Store\TestRunStore;
use MWUnit\TestCase;
use MWUnit\TestSuite;
use MWUnit\Registry\TemplateMockRegistry;
use MWUnit\Runner\Result\TestResult;
use MWUnit\WikitextParser;
use Revision;
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
     * @var TestCase The TestCase that is currently running.
     */
    private $test_case;

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
     * @param callable|null $callback Callback function that gets called after every completed test
	 */
	public function __construct( TestSuite $test_suite, callable $callback = null ) {
		MWUnit::setRunning();

		$this->test_suite = $test_suite;
		$this->callback   = $callback;
		$this->test_run_store = new TestRunStore;

        // Dependency injection
        BaseTestRunner::setTestSuiteRunner( $this );
        TestCaseParserFunction::setTestSuiteRunner( $this );
	}

    /**
     * Runs all tests in the group specified in the constructor.
     *
     * @throws Exception\MWUnitException
     */
	public function run() {
        try {
            if ( !\Hooks::run('MWUnitBeforeFirstTest', [ &$pages ] ) ) {
                return;
            }
        } catch ( \Exception $e ) {
            throw new Exception\MWUnitException( 'mwunit-generic-error-description' );
        }

        foreach ( $this->test_suite as $test_case ) {
			$result = $this->runTestCase( $test_case );

			if ( $result === false ) {
			    throw new Exception\MWUnitException( 'mwunit-failure-running-test', [$test_case->getName()] );
            }

			$this->cleanupAfterFixture( $test_case->getTitle() );
		}

        try {
            \Hooks::run( 'MWUnitAfterTests', [ &$this->test_run_store ] );
        } catch ( \Exception $e ) {
            throw new Exception\MWUnitException( 'mwunit-generic-error-description' );
        }
	}

    /**
     * Increments the total assertion count by the given amount.
     *
     * @param int $count
     */
	public function incrementTotalAssertionCount( int $count ) {
	    $this->total_assertions_count += $count;
    }

    /**
     * Increments the total assertion count by the given amount, or by one if no amount is given.
     *
     * @param int $count
     */
    public function incrementTestCount( int $count = 1 ) {
	    $this->test_count += $count;
    }

    /**
     * Adds a test run.
     *
     * @param TestRun $run
     * @throws Exception\MWUnitException
     */
	public function addTestRun( TestRun $run ) {
	    $this->test_run_store->append( $run );
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
     * Returns the number of failures for the current run.
     *
     * @return int
     */
	public function getNotPassedCount(): int {
		return array_reduce( $this->test_run_store->getTestResults(), function ( int $carry, TestResult $item ) {
			return $carry + ( $item->getResult() === TestResult::T_SUCCESS ? 0 : 1 );
		}, 0 );
	}

    /**
     * Returns the current TestCase object.
     *
     * @return TestCase
     */
    public function getCurrentTestCase(): TestCase {
        return $this->test_case;
    }

    /**
     * Returns true if and only if the given TestCase has been run.
     *
     * @param TestCase $test_case
     * @return bool
     */
    public function testCompleted( TestCase $test_case ): bool {
        foreach ( $this->test_run_store->getTestCases() as $test_run ) {
            assert( $test_run instanceof TestCase );

            if ( $test_run->equals( $test_case ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Called after having run the tests on a page.
     *
     * @param $page Title The article ID of the page
     * @return bool Returns false on failure, true otherwise
     * @throws Exception\MWUnitException
     */
    private function cleanupAfterFixture( Title $page ) {
        try {
            \Hooks::run('MWUnitCleanupAfterPage', [$page]);
        } catch ( \Exception $e ) {
            return false;
        }

        TemplateMockRegistry::getInstance()->reset();
        ParserMockParserFunction::restoreAndReset();

        return true;
    }

    /**
     * Runs a specific test page.
     *
     * @param TestCase $test_case
     * @return bool Returns false on failure, true otherwise
     */
	private function runTestCase( TestCase $test_case ) {
	    $article_id = $test_case->getTitle()->getArticleID();
		$wiki_page = WikiPage::newFromID( $article_id );

		$this->test_case = $test_case;

		if ( $wiki_page === false ) {
			MWUnit::getLogger()->warning( 'Unable to run tests on article {article_id} because it does not exist', [
				'article_id' => $article_id
			] );

			return false;
		}

		if ( $wiki_page->getTitle()->getNamespace() !== NS_TEST ) {
			MWUnit::getLogger()->warning( 'Unable to run tests on article {article} because it is not in the NS_TEST namespace', [
				'article' => $wiki_page->getTitle()->getFullText()
			] );

			return false;
		}

		$content = $wiki_page->getRevision()->getContent( Revision::RAW );

		MWUnit::getLogger()->debug( 'Running tests on article {article_id}', [
		    'article' => $article_id
        ] );

        try {
            WikitextParser::parseContentFromWikiPage($wiki_page, $content, true);
        } catch (MWException $e) {
            return false;
        }

        return true;
	}
}
