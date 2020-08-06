<?php

namespace MWUnit\Runner;

use ConfigException;
use FatalError;
use MWException;
use MWUnit\Exception;
use MWUnit\Injector\TestSuiteRunnerInjector;
use MWUnit\MWUnit;
use MWUnit\ConcreteTestCase;

/**
 * Class BaseTestRunner
 *
 * This class handles the initialisation of a TestRun and the communication with other classes
 * about the result of that TestRun.
 *
 * @package MWUnit
 */
class BaseTestRunner implements TestSuiteRunnerInjector {
    /**
     * @var TestSuiteRunner
     */
    private static $runner;

    /**
	 * @var ConcreteTestCase The test case
	 */
	private $test_case;

    /**
     * @inheritDoc
     */
    public static function setTestSuiteRunner( TestSuiteRunner $runner ) {
        self::$runner = $runner;
    }

	/**
	 * TestCaseRunner constructor.
	 * @param ConcreteTestCase $test_case
	 */
	public function __construct( ConcreteTestCase $test_case ) {
		$this->test_case = $test_case;
	}

	/**
	 * Runs the given TestCase.
	 *
	 * @throws Exception\MWUnitException
	 * @throws FatalError
	 * @throws MWException
	 * @throws ConfigException
     * @return void
	 */
	public function run() {
		if ( !self::$runner->getCurrentTestCase()->equals( $this->test_case ) ) {
			// We don't want to run this test
			return;
		}

		MWUnit::getLogger()->debug( "Running test case {testcase}", [
			'testcase' => $this->test_case->__toString()
		] );

		$run = new TestRun( $this->test_case );
		$run->runTest();

		self::$runner->incrementTotalAssertionCount( $run->getAssertionCount() );
        self::$runner->incrementTestCount();

		$does_not_perform_assertions = $this->test_case->getOption( 'doesnotperformassertions' );

		if ( $does_not_perform_assertions === false && $run->getAssertionCount() === 0 ) {
			$run->setRisky( wfMessage( 'mwunit-no-assertions' )->plain() );
		}

		\Hooks::run( 'MWUnitAfterTestComplete', [ &$run ] );

		self::$runner->addTestResult( $run->getResult() );

		$callback = self::$runner->getCallback();
		if ( is_callable( $callback ) ) {
			MWUnit::getLogger()->debug( "Calling test case completion callback {callback}", [
                self::$runner->getCallback()
			] );

            $callback( $run->getResult() );
		}
	}
}
