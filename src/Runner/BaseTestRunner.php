<?php

namespace MWUnit\Runner;

use Hooks;
use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use MWUnit\ParserFunction\ParserMockParserFunction;
use MWUnit\Profiler;
use MWUnit\Runner\Result\TestResult;
use MWUnit\TemplateMockStore;
use MWUnit\TemplateMockStoreInjector;
use MWUnit\TestCase;
use Parser;
use ParserOptions;
use RequestContext;
use Title;
use User;

/**
 * Class BaseTestRunner
 *
 * This class handles the initialisation of a TestRun object and the cleanup after
 * running a test case, as well as the communication with other classes about the
 * result.
 *
 * @package MWUnit
 */
class BaseTestRunner implements TemplateMockStoreInjector {
	/**
	 * @var TemplateMockStore
	 */
	private static $template_mock_store;

	/**
	 * @var TestCase The test case
	 */
	private $test_case;

	/**
	 * @var array
	 */
	private $globals;

	/**
	 * @var User
	 */
	private $user_serialized;

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var TestRun|false The test run
	 */
	private $test_run = false;

	/**
	 * @var RequestContext
	 */
	private $request_context;

	/**
	 * @var Profiler
	 */
	private $profiler;

	/**
	 * @inheritDoc
	 */
	public static function setTemplateMockStore( TemplateMockStore $store ) {
		self::$template_mock_store = $store;
	}

	/**
	 * TestCaseRunner constructor.
	 *
	 * @param TestCase $test_case
	 * @param Parser $parser
	 * @param RequestContext $request_context
	 * @param Profiler|null $profiler Optional profiler class for profiling test runs
	 */
	public function __construct( TestCase $test_case, Parser $parser, RequestContext $request_context, Profiler $profiler = null ) {
		$this->test_case = $test_case;
		$this->parser = $parser;
		$this->request_context = $request_context;

		$test_run = new TestRun( $test_case );

		try {
			\Hooks::run( "MWUnitAfterInitializeTestRun", [ &$test_run ] );
		} catch ( \Exception $e ) {
			MWUnit::getLogger()->debug( "Exception while running hook MWUnitAfterInitializeTestRun: {e}", [
				'e' => $e->getMessage()
			] );
		}

		$this->test_run = $test_run;
		$this->profiler = Profiler::getInstance() ?? $profiler;
	}

	/**
	 * Returns the TestRun object gained from running the TestCase.
	 *
	 * @return TestRun
	 */
	public function getRun() {
		return $this->test_run;
	}

	/**
	 * Runs the TestCase.
	 *
	 * @return void
	 */
	public function run() {
		MWUnit::getLogger()->debug( "Running test case {testcase}", [
			'testcase' => $this->test_case->getCanonicalName()
		] );

		if ( $this->shouldSkipTest( $message ) ) {
			$this->test_run->setSkipped( $message );
			return;
		}

		$this->runTest();

		if ( $this->shouldMarkRisky( $message ) ) {
			$this->test_run->setRisky( $message );
		}
	}

	/**
	 * Executes the TestRun object in this class.
	 */
	public function runTest() {
		$this->backupUser();
		$this->backupGlobals();

		$context = $this->getContext();

		if ( $context === false ) {
			return;
		}

		try {
			$this->test_run->runTest( $this->parser, ParserOptions::newCanonical( $context ), $this->profiler );
		} finally {
			$this->restore();
		}

		try {
			\Hooks::run( 'MWUnitAfterTestComplete', [ &$run ] );
		} catch ( \Exception $e ) {
			MWUnit::getLogger()->debug( "Exception while running hook MWUnitAfterTestComplete: {e}", [
				'e' => $e->getMessage()
			] );
		}
	}

	/**
	 * Run's the test class's teardown method, resets the parser and restores changed global
	 * state.
	 */
	public function restore() {
		try {
			$this->restoreGlobals();
		} catch ( MWUnitException $e ) {
			MWUnit::getLogger()->emergency( "Unable to restore globals because they are not available" );
		}

		$this->restoreUser();

		try {
			Hooks::run( "MWUnitRestore", [ &$this ] );
		} catch ( \Exception $e ) {
			MWUnit::getLogger()->error(
				"Exception while running hook MWUnitRestore: {e}",
				[ "e" => $e->getMessage() ]
			);
		}

		self::$template_mock_store->reset();

		try {
			ParserMockParserFunction::restoreAndReset();
		} catch ( MWUnitException $e ) {
			MWUnit::getLogger()->emergency( "Unable to restore parser mocks: {e}", [ "e" => $e->getMessage() ] );
		}
	}

	/**
	 * Returns true if and only if the current test run should be skipped (i.e. has @skip or invalid @requires).
	 *
	 * @param string &$message
	 * @return bool
	 */
	public function shouldSkipTest( &$message ): bool {
		if ( $this->doSkipAnnotationCheck( $message ) ) {
			return true;
		}

		if ( $this->doMissingRequiresCheck( $message, \ExtensionRegistry::getInstance() ) ) {
			return true;
		}

		$title = \Title::newFromText( $this->test_case->getCovers(), NS_TEMPLATE );

		if ( $this->doInvalidCoversCheck( $message, $title ) ) {
			return true;
		}

		$skip = false;

		try {
			\Hooks::run( "MWUnitShouldSkipTest", [ &$this->test_case, &$skip, &$message ] );
		} catch ( \Exception $e ) {
			MWUnit::getLogger()->debug( "Exception while running hook MWUnitShouldSkipTest: {e}", [
				'e' => $e->getMessage()
			] );
		}

		return $skip;
	}

	/**
	 * Returns true if and only if the test should be marked as risky.
	 *
	 * @param string|null &$message The "risky" message if the test was deemed risky, null otherwise
	 * @return bool
	 */
	public function shouldMarkRisky( &$message ): bool {
		if ( $this->doAssertionCheck( $message ) ) {
			return true;
		}

		if ( $this->doCoversCheck( $message ) ) {
			return true;
		}

		$risky = false;

		try {
			\Hooks::run( "MWUnitShouldMarkRisky", [ &$this->test_case, &$risky, &$message ] );
		} catch ( \Exception $e ) {
			MWUnit::getLogger()->debug( "Exception while running hook MWUnitShouldMarkRisky: {e}", [
				'e' => $e->getMessage()
			] );
		}

		return $risky;
	}

	/**
	 * Checks if all extensions required through the "requires" annotation are loaded. Returns
	 * true if one or more extensions are NOT loaded, false otherwise.
	 *
	 * @param string &$message
	 * @param \ExtensionRegistry $extension_registry
	 * @return bool
	 */
	public function doMissingRequiresCheck( &$message, \ExtensionRegistry $extension_registry ): bool {
		$requires = $this->test_case->getAttribute( "requires" );

		if ( $requires === false ) {
			return false;
		}

		// Split the "requires" annotation on "comma"
		$required_extensions = explode( ",", $requires );

		// Trim the whitespaces from the resulting items
		$required_extensions = array_map( "trim", $required_extensions );

		// Filter out all empty items
		$required_extensions = array_filter( $required_extensions, function ( string $item ): bool {
			return !empty( $item );
		} );

		// Filter out all installed extensions
		$missing_extensions = array_filter( $required_extensions, function ( string $item ) use ( $extension_registry ): bool {
			return !$extension_registry->isLoaded( $item );
		} );

		$num_missing_extensions = count( $missing_extensions );

		// There are one or more missing extensions.
		if ( $num_missing_extensions > 0 ) {
			$missing_extensions = implode( ", ", $missing_extensions );
			$message_key = "mwunit-skipped-missing-extensions-";
			$message_key .= $num_missing_extensions === 1 ? "singular" : "plural";

			$message = wfMessage( $message_key, $missing_extensions )->plain();

			return true;
		}

		return false;
	}

	/**
	 * Returns true if and only if the test case is annotated with @skip.
	 *
	 * @param &$message
	 * @return bool
	 */
	public function doSkipAnnotationCheck( &$message ): bool {
		$skip = $this->test_case->getAttribute( "skip" );

		if ( $skip === false ) {
			return false;
		}

		$message = wfMessage( "mwunit-skipped" )->plain();

		return true;
	}

	/**
	 * Checks if the test should be skipped because of an invalid "@covers" annotation. Returns true if
	 * and only if the test should be skipped.
	 *
	 * @param string &$message Message that will be set iff the covers check fails
	 * @param Title|null $title The Title object to check for validity
	 * @return bool
	 */
	public function doInvalidCoversCheck( &$message, $title ): bool {
		if ( !$this->test_case->getCovers() ) {
			// This test case does not have a "covers" annotation
			return false;
		}

		if ( !$title instanceof \Title || !$title->exists() ) {
			$message = wfMessage( "mwunit-invalid-covers-annotation" )->plain();
			return true;
		}

		return false;
	}

	/**
	 * Returns true if and only if the test should be marked as risky because it did not conform
	 * to "strict coverage".
	 *
	 * @param string|null &$message The "risky" message if the test was deemed risky, null otherwise
	 * @return bool
	 */
	public function doCoversCheck( &$message ): bool {
		if ( !$this->test_case->getCovers() ) {
			// This test case does not have a "covers" annotation
			return false;
		}

		try {
			$strict_coverage = $this->request_context->getConfig()->get( 'MWUnitStrictCoverage' );
		} catch ( \Exception $e ) {
			$strict_coverage = false;
		}

		if ( !$strict_coverage ) {
			// Strict coverage is not enforced
			return false;
		}

		if ( $this->test_case->getAttribute( 'ignorestrictcoverage' ) !== false ) {
			// Strict coverage is explicitly ignored
			return false;
		}

		$covers = strtolower( $this->test_case->getCovers() );

		if ( in_array( $covers, $this->test_run->getUsedTemplates() ) ) {
			return false;
		}

		$message = wfMessage( 'mwunit-strict-coverage-violation' )->parse();

		return true;
	}

	/**
	 * Backs up globals.
	 */
	private function backupGlobals() {
		$option = $this->request_context->getConfig()->get( 'MWUnitBackupGlobals' );

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
	 *
	 * @throws MWUnitException
	 */
	private function restoreGlobals() {
		try {
			$option = $this->request_context->getConfig()->get( 'MWUnitBackupGlobals' );
		} catch ( \ConfigException $e ) {
			$option = true;
		}

		if ( $option ) {
			if ( !isset( $this->globals ) ) {
				// This execution path should never be reached, because restoreGlobals() should only be called
				// if $this->globals is available.
				throw new MWUnitException( 'mwunit-globals-restored-before-backup' );
			}

			MWUnit::getLogger()->debug( "Restoring globals" );

			// PHP 8.0 does not support writing to the $GLOBALS array directly
			foreach ( $this->globals[ 'GLOBALS' ] as $global_name => $global_value ) {
				$GLOBALS[$global_name] = $global_value;
			}

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
	 * Serializes the current User from RequestContext and stores the result in a class variable.
	 */
	private function backupUser() {
		// We serialize the user to dereference (deep clone) it
		$this->user_serialized = serialize( $this->request_context->getUser() );
	}

	/**
	 * Deserializes the backed up User object and restores the User object globally.
	 */
	private function restoreUser() {
		$this->setUser( unserialize( $this->user_serialized ) );
	}

	/**
	 * Sets the User object globally. This is used to mock other users while running a certain test. The $wgUser
	 * global will and the RequestContext user will be replaced with the given user.
	 *
	 * @param User $user
	 */
	private function setUser( User $user ) {
		$this->request_context->setUser( $user );
		$this->parser->setUser( $user );

		// For extensions still using the old $wgUser variable
		global $wgUser;
		$wgUser = $user;
	}

	/**
	 * Returns true if and only if the current user is allowed to mock other
	 * users while running a test.
	 *
	 * @return bool
	 */
	private function canMockUsers(): bool {
		try {
			$mocking_allowed = $this->request_context->getConfig()->get( 'MWUnitAllowRunningTestAsOtherUser' );
		} catch ( \ConfigException $e ) {
			$mocking_allowed = false;
		}

		if ( !$mocking_allowed ) {
			// Mocking users is disabled
			return false;
		}

		return in_array( 'mwunit-mock-user', $this->request_context->getUser()->getRights() );
	}

	/**
	 * Returns the context to use, or false on failure.
	 *
	 * @return string|User|false|null
	 */
	public function getContext() {
		$context_option = $this->test_case->getAttribute( 'context' );
		$user_option    = $this->test_case->getAttribute( 'user' );

		switch ( $context_option ) {
			case 'canonical':
			case false:
				global $wgVersion;

				return version_compare( $wgVersion, '1.32', '<' ) ? null : 'canonical';
			case 'user':
				if ( !$user_option ) {
					return $this->request_context->getUser();
				}

				if ( !$this->canMockUsers() ) {
					$this->test_run->setSkipped( wfMessage( 'mwunit-missing-permissions-mock-user' )->parse() );

					return false;
				}

				$mock_user = User::newFromName( $user_option );

				if ( !$mock_user instanceof User || $mock_user->isAnon() ) {
					$this->test_run->setSkipped( wfMessage( 'mwunit-invalid-user' )->parse() );

					return false;
				}

				$this->setUser( $mock_user );

				return $mock_user;
			default:
				MWUnit::getLogger()->debug( "Invalid context on {context} on {test}", [
					'context' => $context_option,
					'test'    => $this->test_case->getCanonicalName()
				] );

				$this->test_run->setSkipped( wfMessage( 'mwunit-invalid-context' )->parse() );

				return false;
		}
	}

	/**
	 * Returns true if and only if the test should be marked as risky because it failed to perform
	 * any assertions.
	 *
	 * @param string|null &$message The "risky" message if the test was deemed risky, null otherwise
	 * @return bool
	 */
	public function doAssertionCheck( &$message ) {
		$test_result = $this->test_run->getResult();

		if ( $test_result->getResultConstant() === TestResult::T_RISKY ) {
			// Do not overwrite the result of tests already marked as risky
			return false;
		}

		if ( $test_result->getResultConstant() === TestResult::T_SKIPPED ) {
			// Do not overwrite the result of skipped tests
			return false;
		}

		if ( $this->test_case->getAttribute( 'doesnotperformassertions' ) !== false ) {
			// This test is explicitly marked as not performing any assertions
			return false;
		}

		if ( $this->test_run->getAssertionCount() !== 0 ) {
			return false;
		}

		$message = wfMessage( 'mwunit-no-assertions' )->parse();

		return true;
	}
}
