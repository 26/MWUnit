<?php

namespace MWUnit;

use MWUnit\Exception\MWUnitException;
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
	 * @var TestResult
	 */
	public static $test_result;

	/**
	 * The name of the template that this test case covers, or false if it does not cover a template.
	 *
	 * @var bool|string
	 */
	public static $covered;

	/**
	 * A clone of the parser right before it encountered the first test case. Used for strict coverage checking.
	 *
	 * @var \Parser
	 */
	private static $initial_parser;

	/**
	 * @var TestCase
	 */
	private $test_case;

	/**
	 * @var array
	 */
	private $globals;

	/**
	 * Called when the parser fetches a template. Used for strict coverage checking.
	 *
	 * @param \Parser|bool $parser
	 * @param Title $title
	 * @param \Revision $revision
	 * @param string|false|null &$text
	 * @param array &$deps
	 */
	public static function onParserFetchTemplate(
		$parser,
		Title $title,
		\Revision $revision,
		&$text,
		array &$deps
	) {
		if ( $title->getText() === self::$covered ) {
			self::$test_result->setTemplateCovered();
		}
	}

	/**
	 * TestCaseRun constructor.
	 *
	 * @param TestCase $test_case
	 */
	public function __construct( TestCase $test_case ) {
		$this->test_case = $test_case;

		$test = MWUnit::getCanonicalTestNameFromTestCase( $test_case );

		self::$test_result = new TestResult( $test );
		self::$covered = $test_case->getOption( 'covers' );
	}

	/**
	 * @throws \FatalError
	 * @throws \MWException
	 * @throws MWUnitException
	 */
	public function runTest() {
		// Store a clone of the initial parser, so we can properly perform coverage checks, without
		// breaking fixtures and global state.
		if ( !isset( self::$initial_parser ) ) {
			self::$initial_parser = clone \MediaWiki\MediaWikiServices::getInstance()->getParser();
		}

		$context_option = $this->test_case->getOption( 'context' );

		switch ( $context_option ) {
			case 'canonical':
			case false:
				global $wgVersion;
				$context = version_compare( $wgVersion, '1.32', '<' ) ? null : 'canonical';
				break;
			case 'user':
				$context = \RequestContext::getMain()->getUser();
				break;
			default:
				MWUnit::getLogger()->debug( "Invalid context on {context} on {test}", [
					$context_option,
					MWUnit::getCanonicalTestNameFromTestCase( $this->test_case )
				] );

				self::$test_result->setRisky( wfMessage( 'mwunit-invalid-context' )->plain() );
				return;
		}

		$this->backupGlobals();

		try {
			if ( !self::$covered || $this->isTemplateCached( self::$covered, self::$initial_parser ) ) {
				self::$test_result->setTemplateCovered();
			}

			\Hooks::run( 'MWUnitBeforeRunTestCase', [ &$this->test_case ] );

			// Run test case
			\MediaWiki\MediaWikiServices::getInstance()->getParser()->parse(
				$this->test_case->getInput(),
				$this->test_case->getFrame()->getTitle(),
				\ParserOptions::newCanonical( $context ),
				true,
				false
			);

			$this->checkTemplateCoverage();
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

	/**
	 * Backs up globals.
	 */
	private function backupGlobals() {
		$option = \MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get( 'MWUnitBackupGlobals' );
		if ( $option ) {
			MWUnit::getLogger()->debug( "Backing up globals" );

			$this->globals[ 'GLOBALS' ] 	= $GLOBALS;
			$this->globals[ '_SERVER' ] 	= $_SERVER;
			$this->globals[ '_GET' ]    	= $_GET; // phpcs:ignore
			$this->globals[ '_POST' ]   	= $_POST; // phpcs:ignore
			$this->globals[ '_FILES' ]  	= $_FILES;
			$this->globals[ '_COOKIE' ] 	= $_COOKIE;
			$this->globals[ '_REQUEST' ]	= $_REQUEST;
			$this->globals[ '_ENV' ]    	= $_ENV;
		}
	}

	/**
	 * Restores globals backed up previously. This function should not be called before backupGlobals() is called.
	 * @throws MWUnitException
	 */
	private function restoreGlobals() {
		$option = \MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get( 'MWUnitBackupGlobals' );
		if ( $option ) {
			if ( !isset( $this->globals ) ) {
				MWUnit::getLogger()->emergency( "Unable to restore globals because they are not available" );
				throw new MWUnitException( 'mwunit-globals-restored-before-backup' );
			}

			MWUnit::getLogger()->debug( "Restoring globals" );

			$GLOBALS  	= $this->globals[ 'GLOBALS' ];
			$_SERVER  	= $this->globals[ '_SERVER' ];
			$_GET	  	= $this->globals[ '_GET' ]; // phpcs:ignore
			$_POST    	= $this->globals[ '_POST' ]; // phpcs:ignore
			$_FILES   	= $this->globals[ '_FILES' ];
			$_COOKIE  	= $this->globals[ '_COOKIE' ];
			$_REQUEST 	= $this->globals[ '_REQUEST' ];
			$_ENV 	  	= $this->globals[ '_ENV' ];
		}
	}

	/**
	 * Returns true if and only if the template that is covered is cached.
	 *
	 * @param string $template
	 * @param \Parser $parser
	 * @return bool
	 * @internal
	 */
	private function isTemplateCached(string $template, \Parser $parser ): bool {
		if ( !$template ) {
			return false;
		}

		$title = Title::newFromText( $template, NS_TEMPLATE );

		if ( !$title instanceof Title ) {
			return false;
		}

		$titleText = $title->getPrefixedDBkey();

		if ( isset( $parser->mTplRedirCache[$titleText] ) ) {
			list( $ns, $dbk ) = $parser->mTplRedirCache[$titleText];

			$title = Title::makeTitle( $ns, $dbk );
			$titleText = $title->getPrefixedDBkey();
		}

		if ( isset( $parser->mTplDomCache[$titleText] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the template specified in "covers" is covered and marks $test_result accordingly.
	 * @internal
	 */
	private function checkTemplateCoverage() {
		$strict_coverage = \MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get(
				'MWUnitStrictCoverage'
			) && $this->test_case->getOption( 'ignorestrictcoverage' ) === false;

		if ( $strict_coverage && !self::$test_result->isTemplateCovered() ) {
			self::$test_result->setRisky( wfMessage( 'mwunit-strict-coverage-violation' )->plain() );
		}
	}
}
