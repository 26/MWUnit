<?php

namespace MWUnit\ParserTag;

use Exception;
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

		if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
			// "testcase" is outside of Test namespace
			return MWUnit::error( "mwunit-outside-test-namespace" );
		}

		if ( $input === null ) {
			// Tag is self-closing (i.e. <testcase />)
			return MWUnit::error( "mwunit-empty-testcase" );
		}

		try {
			$test_case = ConcreteTestCase::newFromTag( $input, $args, $parser );
		} catch ( TestCaseException $exception ) {
			return MWUnit::error( $exception->message_name, $exception->arguments );
		}

        if ( self::shouldRunTestcase( $test_case ) ) {
			$runner = new BaseTestRunner( $test_case );
			$runner->run();

			return '';
		}

        try {
            TestCaseRepository::getInstance()->register( $test_case );
        } catch ( TestCaseRegistrationException $exception ) {
            return MWUnit::error( $exception->message_name, $exception->arguments );
        }

		return self::renderTestCaseDebugInformation( $test_case );
	}

	/**
	 * Renders a dialog window for the given TestCase object.
	 *
	 * @param ConcreteTestCase $test_case
	 * @return string The dialog object's HTML (safe)
	 * @internal
	 */
	private static function renderTestCaseDebugInformation(ConcreteTestCase $test_case ): string {
		return sprintf(
			"<div class='messagebox'>" .
						"<pre>MWUnit test\n@name %s\n@group %s\n%s\n--------------------------------\n%s</pre>" .
					"</div>",
			htmlspecialchars( $test_case->getName() ),
			htmlspecialchars( $test_case->getGroup() ),
			htmlspecialchars( self::renderOptions( $test_case->getOptions() ) ),
			htmlspecialchars( $test_case->getInput() )
		);
	}

	/**
	 * Renders the list of optional options for this test case.
	 *
	 * @param array $options
	 * @return string
	 */
	private static function renderOptions( array $options ): string {
        return implode( "\n", array_map( function ( $key, $value ): string {
            return sprintf( "@%s %s", htmlspecialchars( $key ), htmlspecialchars( $value ) );
        }, array_keys( $options ), $options ) );
	}

    /**
     * Returns true if and only if the given TestCase object should be run now. A test should
     * only be run if and only if MWUnit is in "running" mode, the TestSuiteRunner requested the
     * current test case to be run and this test case has not run before.
     *
     * @param TestCase $test_case
     * @return bool
     */
    private static function shouldRunTestcase( TestCase $test_case ): bool {
        return MWUnit::isRunning()
            && self::$test_suite_runner->getCurrentTestCase()->equals( $test_case )
            && !self::$test_suite_runner->testCompleted( $test_case );
    }
}
