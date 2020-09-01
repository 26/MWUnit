<?php

namespace MWUnit\ParserTag;

use ConfigException;
use Exception;
use FatalError;
use MWException;
use MWUnit\Exception\MWUnitException;
use MWUnit\Injector\TestSuiteRunnerInjector;
use MWUnit\ParserData;
use MWUnit\Runner\BaseTestRunner;
use MWUnit\Exception\TestCaseException;
use MWUnit\Exception\TestCaseRegistrationException;
use MWUnit\MWUnit;
use MWUnit\TestCaseRepository;
use MWUnit\ConcreteTestCase;
use MWUnit\Runner\TestSuiteRunner;
use MWUnit\TestCase;
use Parser;

/**
 * Class TestCaseController
 * @package MWUnit
 */
class TestCaseParserTag implements ParserTag, TestSuiteRunnerInjector {
    /**
     * @var TestSuiteRunner
     */
    private static $test_suite_runner;

    /**
     * @inheritDoc
     */
    public static function setTestSuiteRunner( TestSuiteRunner $runner ) {
        self::$test_suite_runner = $runner;
    }

    /**
     * Hooked to the <testcase> parser tag.
     *
     * @param ParserData $data
     * @return string
     * @throws Exception
     */
	public function execute( ParserData $data ) {
	    $parser = $data->getParser();
	    $input  = $data->getInput();
	    $args   = $data->getArguments();

        $result = $this->doExecute( $parser, $input, $args );

        return $result ? $result : '';
	}

    /**
     * @param Parser $parser
     * @param string $input
     * @param array $args
     * @return string
     *
     * @throws ConfigException
     * @throws FatalError
     * @throws MWException
     * @throws MWUnitException
     */
	public function doExecute( Parser $parser, string $input, array $args ) {
        if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
            // "testcase" is outside of Test namespace
            return MWUnit::error( "mwunit-outside-test-namespace" );
        }

        $test_case = ConcreteTestCase::newFromTag( $input, $args, $parser );

        if ( $test_case === false ) {
            return false;
        }

        if ( !self::shouldRunTestcase( $test_case ) ) {
            return false;
        }

        $runner = new BaseTestRunner( $test_case );
        $runner->run();

        return true;
    }

    /**
     * Returns true if and only if the given TestCase object should be run now. A test should
     * only be run if the TestSuiteRunner requested the
     * current test case to be run and this test case has not run before.
     *
     * @param TestCase $test_case
     * @return bool
     */
    private static function shouldRunTestcase( TestCase $test_case ): bool {
        return self::$test_suite_runner &&
            self::$test_suite_runner->getCurrentTestCase()->equals( $test_case ) &&
            !self::$test_suite_runner->testCompleted( $test_case );
    }
}
