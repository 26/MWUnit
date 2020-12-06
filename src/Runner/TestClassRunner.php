<?php

namespace MWUnit\Runner;

use LinkHolderArray;
use MediaWiki\MediaWikiServices;
use MWException;
use MWUnit\MWUnit;
use MWUnit\TestCase;
use MWUnit\TestClass;
use MWUnit\TestRunStore;
use Parser;
use ParserOutput;
use StripState;

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
     * Creates a new TestClassRunner from the given TestClass object.
     *
     * @param TestClass $test_class
     * @return TestClassRunner
     * @throws MWException
     */
    public static function newFromTestClass( TestClass $test_class ): TestClassRunner {
        return new self( $test_class, new TestRunStore() );
    }

    /**
     * TestClassRunner constructor.
     *
     * @param TestClass $test_class
     * @param TestRunStore $test_run_store
     *
     * @throws MWException
     */
    public function __construct( TestClass $test_class, TestRunStore $test_run_store ) {
        $this->test_class = $test_class;

        $title = $test_class->getTitle();
        $wikipage = \WikiPage::factory( $title );
        $parser_options = $wikipage->makeParserOptions( "canonical" );

        $parser = MediaWikiServices::getInstance()->getParser();
        $parser->setTitle( $title );
        $parser->mOptions = $parser_options;
        $parser->setOutputType( Parser::OT_HTML );
        $parser->clearState();

        $this->parser = $parser;
        $this->parser_options = $parser_options;
        $this->title = $title;

        $this->test_run_store = $test_run_store;
    }

    /**
     * Runs the test class.
     */
    public function run() {
        $test_cases = $this->test_class->getTestCases();

        // Run each test case
        foreach ( $test_cases as $test_case ) {
            $this->runSetUp();

            // Clear the parser's magic word cache (so we can override it later)
            $this->parser->mVarCache = [];

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

        $runner = new BaseTestRunner( $test_case, $this->parser );
        $runner->run();

        $run = $runner->getRun();

        $this->total_assertions_count += $run->getAssertionCount();
        $this->run_test_count         += 1;

        if ( isset( $this->callback ) ) {
            call_user_func( $this->callback, $run->getResult() );
        }

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