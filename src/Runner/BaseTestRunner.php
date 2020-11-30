<?php

namespace MWUnit\Runner;

use MWUnit\Exception;
use MWUnit\MWUnit;
use MWUnit\Runner\Result\TestResult;
use MWUnit\TestCase;

/**
 * Class BaseTestRunner
 *
 * This class handles the initialisation of a TestRun object.
 *
 * @package MWUnit
 */
class BaseTestRunner {
	/**
	 * @var TestCase The test case
	 */
	private $test_case;

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
	 * @return TestRun
	 * @throws Exception\MWUnitException
	 */
	public function run(): TestRun {
		MWUnit::getLogger()->debug( "Running test case {testcase}", [
			'testcase' => $this->test_case->getCanonicalName()
		] );

		$run = new TestRun( $this->test_case );

		\Hooks::run( "MWUnitAfterInitializeTestRun", [ &$run ] );

        if ( $this->test_case->getAttribute( "skip" ) !== false ) {
            $message = wfMessage( "mwunit-skipped" )->plain();
            $run->setSkipped( $message );
            return $run;
        }

        $requires = $this->test_case->getAttribute( "requires" );
        if ( $requires !== false ) {
            $required_extensions = explode( ",", $requires );
            $required_extensions = array_map( "trim", $required_extensions );

            $extension_registry = \ExtensionRegistry::getInstance();

            $missing_extensions = [];

            foreach ( $required_extensions as $required_extension ) {
                if ( !$extension_registry->isLoaded( $required_extension ) ) {
                    $missing_extensions[] = $required_extension;
                }
            }

            if ( count( $missing_extensions ) > 0 ) {
                $missing_extensions = implode( ", ", $missing_extensions );
                $message = wfMessage( "mwunit-skipped-missing-extensions", $missing_extensions )->plain();
                $run->setSkipped( $message );

                return $run;
            }
        }

		$run->runTest();

		\Hooks::run( 'MWUnitAfterTestComplete', [ &$run ] );

		$this->doAssertionCheck( $run );

		return $run;
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

		if ( $test_result->getResultConstant() === TestResult::T_SKIPPED ) {
		    // Do not overwrite the result of skipped tests
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
