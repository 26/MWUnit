<?php

namespace MWUnit;

/**
 * Class TestCaseRun
 * @package MWUnit
 */
class TestCaseRun {
	/**
	 * @var TestResult
	 */
	public static $test_result;

	/**
	 * @var TestCase
	 */
	private $test_case;

	/**
	 * TestCaseRun constructor.
	 * @param TestCase $test_case
	 * @throws Exception\MWUnitException
	 */
	public function __construct( \MWUnit\TestCase $test_case ) {
		$this->test_case = $test_case;
		self::$test_result = new TestResult( MWUnit::getCanonicalTestNameFromTestCase( $test_case ) );
	}

	public function runTest() {
		$context_option = $this->test_case->getOption( 'context' );
		if ( $context_option !== false ) {
			switch ( $context_option ) {
				case 'canonical':
					$context = 'canonical';
					break;
				case 'user':
					$context = $this->test_case->getParser()->getUser();
					break;
				default:
					self::$test_result->setRisky();
					self::$test_result->setRiskyMessage( 'mwunit-invalid-context' );
					return;
			}
		} else {
			$context = 'canonical';
		}

		$parser = ( \MediaWiki\MediaWikiServices::getInstance() )->getParser();

		// Run test cases
		$parser->parse(
			$this->test_case->getInput(),
			$this->test_case->getFrame()->getTitle(),
			\ParserOptions::newCanonical( $context ),
			true,
			false
		);
	}

	/**
	 * Returns the number of assertions ran for this test case.
	 *
	 * @return int
	 */
	public function getAssertionCount(): int {
		return self::$test_result->getAssertionCount();
	}

	/**
	 * Returns the result of this test case run as a TestResult object.
	 *
	 * @return TestResult
	 */
	public function getTestResult(): TestResult {
		return self::$test_result;
	}
}
