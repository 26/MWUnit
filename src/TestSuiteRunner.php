<?php

namespace MWUnit;

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
	public static $tests;

	/**
	 * The results of this unit test run.
	 *
	 * @var array An array of TestResult objects
	 */
	public static $test_results = [];

	/**
	 * @var int The total number of assertions for the current run.
	 */
	public static $total_assertions_count = 0;

	/**
	 * @var int The total number of tests for the current run.
	 */
	public static $total_test_count = 0;

	/**
	 * @var callable
	 */
	public static $callback;

	/**
	 * UnitTestRunner constructor.
	 * @param array $tests
	 */
	public function __construct( array $tests ) {
		MWUnit::setRunning();
		self::$tests = $tests;
	}

	/**
	 * Runs all tests in the group specified in the constructor.
	 *
	 * @param callable|null $callback Callback function that gets called after every completed test
	 * @throws \FatalError
	 * @throws \MWException
	 */
	public function run( callable $callback = null ) {
		$pages = array_unique( array_values( self::$tests ) );

		if ( count( $pages ) === 0 ) {
			return;
		}

		$result = \Hooks::run( 'MWUnitBeforeFirstTestHook', [ &$pages ] );

		if ( !$result ) {
			return;
		}

		self::$callback = $callback;
		foreach ( $pages as $page ) {
			$this->runTestsOnPage( $page );
		}
	}

	/**
	 * Returns the result of the current run.
	 *
	 * @return array An array of TestResult objects
	 */
	public function getResults(): array {
		return self::$test_results;
	}

	/**
	 * Returns the total number of assertions for the current run.
	 *
	 * @return int The total number of assertions for the current run
	 */
	public function getTotalAssertionsCount(): int {
		return self::$total_assertions_count;
	}

	/**
	 * Returns the number of tests ran.
	 *
	 * @return int The total number of tests ran
	 */
	public function getTotalTestCount(): int {
		return self::$total_test_count;
	}

	/**
	 * Returns the number of failures for the current run.
	 *
	 * @return int
	 */
	public function getNotPassedCount(): int {
	    return array_reduce( self::$test_results, function( int $carry, TestResult $item ) {
		    return $carry + ( $item->didTestSucceed() ? 0 : 1 );
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
	 * Runs a specific test page.
	 *
	 * @param int $article_id
	 */
	private function runTestsOnPage( int $article_id ) {
		$wiki_page = \WikiPage::newFromID( $article_id );

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
			$content = $wiki_page->getRevision()->getContent( \Revision::RAW );
			$parser  = ( \MediaWiki\MediaWikiServices::getInstance() )->getParser()->getFreshParser();
			$parser_options = $wiki_page->makeParserOptions( "canonical" );
			$text = \ContentHandler::getContentText( $content );
		} catch ( \MWException $e ) {
			MWUnit::getLogger()->debug( 'Unable to create fresh parser for test suite {article}: {exception}', [
				'article' => $wiki_page->getTitle()->getFullText(),
				'exception' => $e
			] );

			return;
		}

		MWUnit::getLogger()->debug( 'Running tests on article {article_id}', [
			'article' => $article_id
		] );

		// Run test cases
		$parser->parse( $text, $wiki_page->getTitle(), $parser_options );
	}
}
