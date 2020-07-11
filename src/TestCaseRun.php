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
	 * @var array
	 */
	private $globals;

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

		switch ( $context_option ) {
			case 'canonical':
			case false:
				global $wgVersion;
				$context = version_compare( $wgVersion, '1.32', '<' ) ? null : 'canonical';
				break;
			case 'user':
				$context = $this->test_case->getParser()->getUser();
				break;
			default:
				self::$test_result->setRisky();
				self::$test_result->setRiskyMessage( 'mwunit-invalid-context' );
				return;
		}

		$this->backupGlobals();

		try {
			// Run test cases
			( \MediaWiki\MediaWikiServices::getInstance() )->getParser()->parse(
				$this->test_case->getInput(),
				$this->test_case->getFrame()->getTitle(),
				\ParserOptions::newCanonical( $context ),
				true,
				false
			);
		} finally {
			$this->restoreGlobals();
		}
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

	private function backupGlobals() {
		$option = \MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get( 'MWUnitBackupGlobals' );
		if ( $option ) {
			$this->globals[ 'GLOBALS' ] 	= $GLOBALS;
			$this->globals[ '_SERVER' ] 	= $_SERVER;
			$this->globals[ '_GET' ]    	= $_GET;
			$this->globals[ '_POST' ]   	= $_POST;
			$this->globals[ '_FILES' ]  	= $_FILES;
			$this->globals[ '_COOKIE' ] 	= $_COOKIE;
			$this->globals[ '_SESSION' ]	= $_SESSION;
			$this->globals[ '_REQUEST' ]	= $_REQUEST;
			$this->globals[ '_ENV' ]    	= $_ENV;
		}
	}

	private function restoreGlobals() {
		$option = \MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get( 'MWUnitBackupGlobals' );
		if ( $option ) {
			$GLOBALS  	= $this->globals[ 'GLOBALS' ];
			$_SERVER  	= $this->globals[ '_SERVER' ];
			$_GET	  	= $this->globals[ '_GET' ];
			$_POST    	= $this->globals[ '_POST' ];
			$_FILES   	= $this->globals[ '_FILES' ];
			$_COOKIE  	= $this->globals[ '_COOKIE' ];
			$_SESSION 	= $this->globals[ '_SESSION' ];
			$_REQUEST 	= $this->globals[ '_REQUEST' ];
			$_ENV 	  	= $this->globals[ '_ENV' ];
		}
	}
}
