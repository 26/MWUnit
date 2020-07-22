<?php

namespace MWUnit\Controller;

use MWUnit\Exception\MWUnitException;
use MWUnit\Exception\TestCaseException;
use MWUnit\Exception\TestCaseRegistrationException;
use MWUnit\MWUnit;
use MWUnit\TestCase;
use MWUnit\TestCaseRegister;
use MWUnit\TestCaseRunner;

/**
 * Class TestCaseController
 * @package MWUnit
 */
class TestCaseController {
	/**
	 * The callback function for the "testcase" tag.
	 *
	 * @param string $input Input between the "<testcase>" and "</testcase>" tags; null if the tag is "closed"
	 * @param array $args Tag arguments entered like HTML tag attributes; a key,value pair indexed by attribute name
	 * @param \Parser $parser The parent parser (Parser object)
	 * @param \PPFrame $frame The parent frame (PPFrame object)
	 * @return string The output of the tag
	 * @throws MWUnitException
	 * @throws \FatalError
	 * @throws \MWException
	 * @internal
	 */
	public static function handleTestCase( $input, array $args, \Parser $parser, \PPFrame $frame ) {
		if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
			// "testcase" is outside of Test namespace
			return MWUnit::error( "mwunit-outside-test-namespace" );
		}

		if ( $input === null ) {
			// Tag is self-closing (i.e. <testcase />)
			return MWUnit::error( "mwunit-empty-testcase" );
		}

		$is_running = MWUnit::isRunning();

		try {
			$test_case = TestCase::newFromTag( $input, $args, $parser, $frame );
		} catch ( TestCaseException $exception ) {
			return MWUnit::error( $exception->message_name, $exception->arguments );
		}

		if ( $is_running ) {
			$runner = new TestCaseRunner( $test_case );
			$runner->run();
		} else {
			try {
				TestCaseRegister::register( $test_case );
			} catch ( TestCaseRegistrationException $exception ) {
				return MWUnit::error( $exception->message_name, $exception->arguments );
			}
		}

		return self::renderTestCaseDebugInformation( $test_case );
	}

	/**
	 * Renders a dialog windows for the given TestCase object.
	 *
	 * @param TestCase $test_case
	 * @return string The dialog object's HTML (safe)
	 * @internal
	 */
	private static function renderTestCaseDebugInformation( TestCase $test_case ): string {
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
		$buffer = [];
		foreach ( $options as $name => $value ) {
			$buffer[] = "@$name $value";
		}

		return implode( "\n", $buffer );
	}
}
