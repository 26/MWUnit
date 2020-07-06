<?php

namespace MWUnit;

class UnitTestRunner {
	/**
	 * An associative array of the tests to be ran, where the key is the test identifier
	 * and the value is the article ID the test is on.
	 *
	 * @var string
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
	 * UnitTestRunner constructor.
	 * @param array $tests
	 */
	public function __construct( array $tests ) {
		MWUnit::setRunning();

		self::$tests = $tests;
	}

	/**
	 * Runs all tests in the group specified in the constructor.
	 */
	public function run() {
		$pages = array_unique( array_values( self::$tests ) );

		if ( count( $pages ) === 0 ) {
			return;
		}

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
	public function getFailuresCount(): int {
		$failures = 0;
		foreach ( self::$test_results as $result ) {
			if ( !$result->didTestSucceed() ) { $failures++;
			}
		}

		return $failures;
	}

	/**
	 * Runs a specific test page.
	 *
	 * @param int $article_id
	 */
	private function runTestsOnPage( int $article_id ) {
		$wiki_page = \WikiPage::newFromID( $article_id );

		if ( $wiki_page === false ) {
			return;
		}

		if ( $wiki_page->getTitle()->getNamespace() !== NS_TEST ) {
			return;
		}

		try {
			$content = $wiki_page->getRevision()->getContent( \Revision::RAW );
			$parser  = ( \MediaWiki\MediaWikiServices::getInstance() )->getParser();
			$text = \ContentHandler::getContentText( $content );
		} catch ( \MWException $e ) {
			return;
		}

		// Run test cases
		$parser->parse( $text, $wiki_page->getTitle(), $wiki_page->makeParserOptions( "canonical" ) );
	}
}
