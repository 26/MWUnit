<?php

namespace MWUnit\Runner;

use ConfigException;
use FatalError;
use MWException;
use MWUnit\Exception;
use MWUnit\Injector\TestSuiteRunnerInjector;
use MWUnit\MWUnit;
use MWUnit\TestCase;
use MWUnit\Runner\Result\TestResult;

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
	 * @var TestCase The test case
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
	 * @param TestCase $test_case
	 */
	public function __construct( TestCase $test_case ) {
		$this->test_case = $test_case;
	}

	/**
	 * Runs the TestCase.
	 *
	 * @throws Exception\MWUnitException
	 * @throws FatalError
	 * @throws MWException
     * @return void
	 */
	public function run() {
	    MWUnit::getLogger()->debug( "Running test case {testcase}", [
			'testcase' => $this->test_case->getCanonicalName()
		] );

		$run = new TestRun( $this->test_case );
		$run->runTest();

        \Hooks::run( 'MWUnitAfterTestComplete', [ &$run ] );

		self::$runner->incrementTotalAssertionCount( $run->getAssertionCount() );
        self::$runner->incrementTestCount();

        $this->doAssertionCheck( $run );

		if ( self::$runner->hasCallback() ) {
            self::$runner->getCallback()( $run->getResult() );
		}

        self::$runner->addTestRun( $run );
	}

    /**
     * Checks whether or not the test case performed any assertions and marks it as "Risky" when
     * no assertions were performed.
     * @param TestRun $run
     */
	private function doAssertionCheck( TestRun $run ) {
        $test_result = $run->getResult();

        if ( $test_result->getResultConstant() === TestResult::T_RISKY ) {
            // Do not overwrite the result of tests already marked as risky
            return;
        }

        if ( $this->test_case->getAttribute( 'doesnotperformassertions' ) !== false ) {
            // This test is explicitly marked as not performing any assertions
            return;
        }

        if ( $run->getAssertionCount() === 0 ) {
            $run->setRisky( wfMessage( 'mwunit-no-assertions' )->parse() );
        }
    }
}
