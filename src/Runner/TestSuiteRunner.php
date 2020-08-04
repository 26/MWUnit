<?php

namespace MWUnit\Runner;

use ContentHandler;
use MediaWiki\MediaWikiServices;
use MWException;
use MWUnit\Controller\ParserMockController;
use MWUnit\Exception;
use MWUnit\MWUnit;
use MWUnit\Registry\MockRegistry;
use MWUnit\Runner\Result\SuccessTestResult;
use MWUnit\Runner\Result\TestResult;
use MWUnit\WikitextParser;
use Revision;
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
	 * An associative array of the tests to be ran, where the key is the test identifier
	 * and the value is the article ID the test is on.
	 *
	 * @var array
	 */
	private $tests;

	/**
	 * The results of this unit test run.
	 *
	 * @var array An array of TestResult objects
	 */
	private $test_results = [];

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
	 * UnitTestRunner constructor.
	 * @param array $tests
	 */
	public function __construct( array $tests ) {
		MWUnit::setRunning();

		$this->tests = $tests;

        // Dependency injection
        BaseTestRunner::setTestSuiteRunner( $this );
	}

	/**
	 * Runs all tests in the group specified in the constructor.
	 *
	 * @param callable|null $callback Callback function that gets called after every completed test
	 * @return bool
	 *
	 * @throws Exception\MWUnitException
	 */
	public function run( callable $callback = null ) {
		$pages = array_unique( array_values( $this->tests ) );

		if ( count( $pages ) === 0 ) {
			return false;
		}

		$result = \Hooks::run( 'MWUnitBeforeFirstTest', [ &$pages ] );

		if ( !$result ) {
			return false;
		}

        $this->callback = $callback;
		foreach ( $pages as $page ) {
			$this->runTestsOnPage( $page );
			$this->cleanupAfterFixture( $page );
		}

		\Hooks::run( 'MWUnitAfterTests', [ &$this->test_results ] );

		return true;
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
     * Adds a test result.
     *
     * @param TestResult $result
     */
	public function addTestResult( TestResult $result ) {
	    $this->test_results[] = $result;
    }

    /**
     * Returns the tests in this test sutie.
     *
     * @return array
     */
	public function getTests(): array {
	    return $this->tests;
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
	 * Returns the result of the current run.
	 *
	 * @return array An array of TestResult objects
	 */
	public function getResults(): array {
		return $this->test_results;
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
		return array_reduce( $this->test_results, function ( int $carry, TestResult $item ) {
			return $carry + ( $item->getResult() === TestResult::T_SUCCESS ? 0 : 1 );
		}, 0 );
	}

	/**
	 * Returns an array of failed TestResult objects.
	 *
	 * @return array
	 */
	public function getFailedTests(): array {
		return array_filter( $this->getResults(), function ( TestResult $result ) {
			return $result->getResult() === TestResult::T_FAILED;
		} );
	}

	/**
	 * Returns an array of risky TestResult objects.
	 *
	 * @return array
	 */
	public function getRiskyTests(): array {
		return array_filter( $this->getResults(), function ( TestResult $result ) {
			return $result->getResult() === TestResult::T_RISKY;
		} );
	}

    /**
     * Returns true if and only if all tests were performed. This function
     * checks whether the list of performed test names is equal to the
     * list of tests that needed to be ran.
     *
     * @return bool True if all tests are performed, false otherwise
     */
    public function areAllTestsPerformed(): bool {
        return count(
                array_diff(
                    array_keys( $this->tests ),
                    $this->getTestsRun()
                )
            ) === 0;
    }

    /**
     * Returns an array of the canonical test names of the tests that actually ran.
     *
     * @return string[] Array of strings containing the canonical names of the tests that were ran
     */
    private function getTestsRun(): array {
        return array_map( function ( TestResult $test_result ): string {
            return $test_result->getCanonicalTestName();
        }, $this->test_results );
    }

    /**
     * Called after having run the tests on a page.
     *
     * @param $page int The article ID of the page
     * @throws Exception\MWUnitException
     */
    private function cleanupAfterFixture( $page ) {
        \Hooks::run( 'MWUnitCleanupAfterPage', [ $page ] );

        MockRegistry::getInstance()->reset();
        ParserMockController::restoreAndReset();
    }

	/**
	 * Runs a specific test page.
	 *
	 * @param int $article_id
	 */
	private function runTestsOnPage( int $article_id ) {
		$wiki_page = WikiPage::newFromID( $article_id );

		if ( $wiki_page === false ) {
			MWUnit::getLogger()->warning( 'Unable to run tests on article {article_id} because it does not exist', [
				'article_id' => $article_id
			] );

			return;
		}

		if ( $wiki_page->getTitle()->getNamespace() !== NS_TEST ) {
			MWUnit::getLogger()->warning( 'Unable to run tests on article {article} because it is not in the NS_TEST namespace', [
				'article' => $wiki_page->getTitle()->getFullText()
			] );

			return;
		}

		try {
			$content = $wiki_page->getRevision()->getContent( Revision::RAW );
		} catch ( MWException $e ) {
			MWUnit::getLogger()->debug( 'Unable to create fresh parser for test suite {article}: {exception}', [
				'article' => $wiki_page->getTitle()->getFullText(),
				'exception' => $e
			] );

			return;
		}

		MWUnit::getLogger()->debug( 'Running tests on article {article_id}', [ 'article' => $article_id ] );
        WikitextParser::parseContentFromWikiPage( $wiki_page, $content, true );
	}
}
