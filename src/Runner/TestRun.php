<?php

namespace MWUnit\Runner;

use Hooks;
use MWUnit\MWUnit;
use MWUnit\ParserFunction\AssertionParserFunction;
use MWUnit\ParserFunction\TemplateMockParserFunction;
use MWUnit\ParserFunction\VarDumpParserFunction;
use MWUnit\Profiler;
use MWUnit\Runner\Result\FailureTestResult;
use MWUnit\Runner\Result\RiskyTestResult;
use MWUnit\Runner\Result\SkippedTestResult;
use MWUnit\Runner\Result\SuccessTestResult;
use MWUnit\Runner\Result\TestResult;
use MWUnit\TestCase;
use Parser;
use ParserOptions;
use Revision;
use Title;

/**
 * Class TestRun
 *
 * This class runs a test case. This class is always initiated from the BaseTestRunner class. The
 * communication with other classes about the result of this TestRun is delegated to the
 * BaseTestRunner class.
 *
 * @package MWUnit
 */
class TestRun {
	/**
	 * @var array Array of templates used in this test run.
	 */
	public static $templates_used;

	/**
	 * @var string[]
	 */
	public $test_outputs = [];

	/**
	 * @var int Number of assertions used in this test run.
	 */
	private $assertion_count = 0;

	/**
	 * @var TestResult The result of this test run.
	 */
	private $result = null;

	/**
	 * @var TestCase
	 */
	private $test_case;

	/**
	 * @var float
	 */
	private $execution_time = 0.0;

	/**
	 * Called when the parser fetches a template. Used for strict coverage checking.
	 *
	 * @param Parser|bool $parser
	 * @param Title $title
	 * @param Revision $revision
	 * @param string|false|null &$text
	 * @param array &$deps
	 */
	public static function onParserFetchTemplate(
		$parser,
		Title $title,
		$revision,
		&$text,
		&$deps
	) {
		self::$templates_used[] = strtolower( $title->getText() );
	}

	/**
	 * TestCaseRun constructor.
	 *
	 * @param TestCase $test_case
	 */
	public function __construct( TestCase $test_case ) {
		$this->test_case = $test_case;

		self::$templates_used = [];

		// Dependency injection
		AssertionParserFunction::setTestRun( $this );
		TemplateMockParserFunction::setTestRun( $this );
		VarDumpParserFunction::setTestRun( $this );
	}

	/**
	 * Increments the assertion count for this run.
	 */
	public function incrementAssertionCount() {
		$this->assertion_count++;
	}

	/**
	 * Returns true if and only if the test has finished and a result is available.
	 *
	 * @return bool
	 */
	public function resultAvailable(): bool {
		return isset( $this->result );
	}

	/**
	 * Sets the result fot his TestRun.
	 *
	 * @param TestResult $result
	 */
	public function setResult( TestResult $result ) {
		$this->result = $result;
	}

	/**
	 * Sets the result of this test run to "Success".
	 */
	public function setSuccess() {
		$this->result = new SuccessTestResult(
			$this->test_case
		);
	}

	/**
	 * Sets the result of this test run to "Risky".
	 *
	 * @param string $message Localised message to use for the "risky" message.
	 */
	public function setRisky( string $message ) {
		$this->result = new RiskyTestResult(
			$message,
			$this->test_case
		);
	}

	/**
	 * Sets the result of this test run to "Failure".
	 *
	 * @param string $message Localised message to use for the "failure" message.
	 */
	public function setFailure( string $message ) {
		$this->result = new FailureTestResult(
			$message,
			$this->test_case
		);
	}

	/**
	 * Sets the result of this test run to "Skipped".
	 *
	 * @param string $message Localised message to use for the "skipped" message.
	 */
	public function setSkipped( string $message ) {
		$this->result = new SkippedTestResult(
			$message,
			$this->test_case
		);
	}

	/**
	 * Returns the result of this test run.
	 *
	 * @return TestResult
	 * @throws \LogicException If the function is called before a result is available
	 */
	public function getResult(): TestResult {
		if ( !isset( $this->result ) || $this->result === null ) {
			throw new \LogicException( __METHOD__ . " called before the initialization of result" );
		}

		return $this->result;
	}

	/**
	 * Returns the TestOutputCollector object for this TestRun.
	 *
	 * @return string[]
	 */
	public function getTestOutputs(): array {
		return $this->test_outputs;
	}

	/**
	 * Returns the number of assertions ran for this test case.
	 *
	 * @return int
	 */
	public function getAssertionCount(): int {
		return $this->assertion_count;
	}

	/**
	 * Returns the test case associated with this run.
	 *
	 * @return TestCase
	 */
	public function getTestCase(): TestCase {
		return $this->test_case;
	}

	/**
	 * Returns the execution time of this test.
	 *
	 * @return float
	 */
	public function getExecutionTime(): float {
		return $this->execution_time;
	}

	/**
	 * Runs the test case. A Result object is guaranteed to be available if this function
	 * finished successfully.
	 *
	 * @param Parser $parser The parser to use for running the tests
	 * @param ParserOptions $options The parser options which which to initialize the parsing
	 * @param Profiler|null $profiler Optional profiler to profile this test class
	 */
	public function runTest( Parser $parser, ParserOptions $options, Profiler $profiler = null ) {
		try {
			// Avoid PHP 7.1 warning when passing $this as reference
			$test_run = $this;
			$result = Hooks::run( 'MWUnitBeforeRunTestCase', [ &$this->test_case, &$test_run, &$context ] );

			if ( $result === false ) {
				// The hook returned false, therefore another extension asked MWUnit to skip this
				// test
				return;
			}
		} catch ( \Exception $e ) {
			MWUnit::getLogger()->error(
				"Exception while running hook MWUnitBeforeRunTestCase: {e}",
				[ "e" => $e->getMessage() ]
			);
		}

		try {
			if ( isset( $profiler ) ) {
				$profiler->flag();
			}

			$parser->parse(
				$this->test_case->getContent(),
				$this->test_case->getTestPage(),
				$options,
				true,
				false
			);
		} finally {
			if ( !isset( $this->result ) ) {
				$this->setSuccess();
			}

			if ( isset( $profiler ) ) {
				$profiler->flag();
				$this->execution_time = $profiler->getFlagExecutionTime();
			}
		}
	}

	/**
	 * Returns an array of templates used in this test run.
	 *
	 * @return array
	 */
	public function getUsedTemplates() {
		return array_unique( self::$templates_used );
	}
}
