<?php

namespace MWUnit\Runner;

use MediaWiki\MediaWikiServices;
use MWUnit\MWUnit;
use MWUnit\Runner\Result\TestResult;
use MWUnit\TestCase;
use MWUnit\TestClass;
use MWUnit\TestRunStore;
use Parser;
use RequestContext;
use WikiPage;

class TestClassRunner {
	/**
	 * @var TestClass
	 */
	private $test_class;

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var \ParserOptions
	 */
	private $parser_options;

	/**
	 * @var \Title
	 */
	private $title;

	/**
	 * @var TestRunStore
	 */
	private $test_run_store;

	/**
	 * @var int
	 */
	private $total_assertions_count = 0;

	/**
	 * @var int
	 */
	private $run_test_count = 0;

	/**
	 * @var callable|null
	 */
	private $callback;

	/**
	 * @var RequestContext
	 */
	private $request_content;

	/**
	 * Creates a new TestClassRunner from the given TestClass object.
	 *
	 * @param TestClass $test_class
	 * @return TestClassRunner
	 */
	public static function newFromTestClass( TestClass $test_class ): TestClassRunner {
		return new self( $test_class, new TestRunStore() );
	}

	/**
	 * TestClassRunner constructor.
	 *
	 * @param TestClass $test_class The TestClass object to run
	 * @param TestRunStore $test_run_store The TestRunStore object to add the results to
	 * @param callable|null $callback An optional callback to call after each TestRun
	 * @param Parser|null $parser Optionally the parser to use for parsing, otherwise the parser service will be used
	 * @param RequestContext|null $request_content Optionally the request context to use
	 */
	public function __construct( TestClass $test_class, TestRunStore $test_run_store, callable $callback = null, Parser $parser = null, RequestContext $request_content = null ) {
		$this->test_class = $test_class;

		$title = $test_class->getTitle();
		$wikipage = new WikiPage( $title );
		$parser_options = $wikipage->makeParserOptions( "canonical" );

		if ( !isset( $parser ) ) {
			$parser = MediaWikiServices::getInstance()->getParser();
			$parser->setTitle( $title );
			$parser->mOptions = $parser_options;
			$parser->setOutputType( Parser::OT_HTML );
			$parser->clearState();
		}

		if ( !isset( $request_content ) ) {
			$this->request_content = RequestContext::getMain();
		}

		$this->parser = $parser;
		$this->parser_options = $parser_options;
		$this->title = $title;

		$this->test_run_store = $test_run_store;
		$this->callback = $callback;
	}

	/**
	 * Runs the test class.
	 */
	public function run() {
		$test_cases = $this->test_class->getTestCases();

		// Run each test case
		foreach ( $test_cases as $test_case ) {
			$this->runSetUp();

			if ( version_compare( $GLOBALS["wgVersion"], "1.35.0" ) < 0 ) {
				// Clear the parser's magic word cache (so we can override magic words)
				// In MediaWiki 1.35, the mVarCache class attribute is private :(
				$this->parser->mVarCache = [];
			}

			$this->runTestCase( $test_case );
			$this->runTearDown();
		}
	}

	/**
	 * Returns the number of assertions called in this test class.
	 *
	 * @return int
	 */
	public function getAssertionCount(): int {
		return $this->total_assertions_count;
	}

	/**
	 * Returns the number of tests ran in this test class.
	 *
	 * @return int
	 */
	public function getRunTestCount(): int {
		return $this->run_test_count;
	}

	/**
	 * Executes the callback if it is available with the given TestResult.
	 *
	 * @param TestResult $test_result
	 */
	public function callback( TestResult $test_result ) {
		if ( isset( $this->callback ) ) {
			call_user_func( $this->callback, $test_result );
		}
	}

	/**
	 * Runs the given test case.
	 *
	 * @param TestCase $test_case
	 */
	private function runTestCase( TestCase $test_case ) {
		try {
			$result = \Hooks::run( "MWUnitBeforeInitializeBaseTestRunner", [ &$test_case, &$test_class ] );

			if ( $result === false ) {
				// The hook returned false; skip this test case
				return;
			}
		} catch ( \Exception $e ) {
			MWUnit::getLogger()->error(
				"Exception while running hook MWUnitBeforeInitializeBaseTestRunner: {e}",
				[ "e" => $e->getMessage() ]
			);

			return;
		}

		$runner = new BaseTestRunner( $test_case, $this->parser, $this->request_content );
		$runner->run();

		$run = $runner->getRun();

		$this->total_assertions_count += $run->getAssertionCount();
		$this->run_test_count         += 1;

		$this->callback( $run->getResult() );

		$this->test_run_store->append( $run );
	}

	/**
	 * Runs the test class's setUp method.
	 */
	private function runSetUp() {
		$this->parser->parse( $this->test_class->getSetUp(), $this->title, $this->parser_options );
	}

	/**
	 * Runs the test class's tearDown method.
	 */
	private function runTearDown() {
		$this->parser->parse( $this->test_class->getTearDown(), $this->title, $this->parser_options );
	}
}
