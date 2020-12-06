<?php

namespace MWUnit\Runner;

use Hooks;
use MediaWiki\MediaWikiServices;
use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use MWUnit\Runner\Result\TestResult;
use MWUnit\TestCase;
use Parser;
use RequestContext;
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
class BaseTestRunner {
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
    private $user;

    /**
     * @var TestRun|false The test run
     */
    private $test_run = false;

    /**
	 * TestCaseRunner constructor.
	 * @param TestCase $test_case
	 */
	public function __construct( TestCase $test_case ) {
		$this->test_case = $test_case;
	}

    /**
     * Returns the TestRun object gained from running the TestCase, or false if
     * the TestCase was not run (yet).
     *
     * @return TestRun|false
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

		$this->initializeTestRun();

		if ( $this->shouldSkip( $message ) ) {
            $this->test_run->setSkipped( $message );
		    return;
        }

        $this->executeTestRun();

		if ( $this->doAssertionCheck() ) {
            $this->test_run->setRisky( wfMessage( 'mwunit-no-assertions' )->parse() );
        }

        if ( $this->doCoversCheck() ) {
            $this->test_run->setRisky( wfMessage( 'mwunit-strict-coverage-violation' )->parse() );
        }
	}

    /**
     * Executes the TestRun object in this class.
     */
    private function executeTestRun() {
	    $parser = $this->getParser();

        $this->backupUser();
        $this->backupGlobals();

        try {
            $context = $this->getContext();

            if ( $context === false ) {
                return;
            }

            $this->test_run->runTest( $parser, $context );
        } finally {
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

            // Reset the parser template DOM cache. Otherwise onParserFetchTemplate is only called
            // once and coverage checks cannot be performed.
            $parser->mTplDomCache = [];
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
     * Initializes the TestRun object.
     */
	private function initializeTestRun() {
        $this->test_run = new TestRun( $this->test_case );

        try {
            \Hooks::run( "MWUnitAfterInitializeTestRun", [ &$this->test_run ] );
        } catch ( \Exception $e ) {
            MWUnit::getLogger()->debug( "Exception while running hook MWUnitAfterInitializeTestRun: {e}", [
                'e' => $e->getMessage()
            ] );
        }
    }

    /**
     * Returns true if and only if the current test run should be skipped (i.e. has @skip or invalid @requires).
     *
     * @param string $message
     * @return bool
     */
    private function shouldSkip( &$message ): bool {
        if ( $this->doSkipAnnotationCheck( $message ) ) {
            return true;
        }

        if ( $this->doMissingRequiresCheck( $message ) ) {
            return true;
        }

        if ( $this->doInvalidCoversCheck( $message ) ) {
            return true;
        }

        $skip = false;

        try {
            \Hooks::run( "MWUnitOnShouldSkip", [ &$skip, &$message ] );
        } catch ( \Exception $e ) {
            MWUnit::getLogger()->debug( "Exception while running hook MWUnitOnShouldSkip: {e}", [
                'e' => $e->getMessage()
            ] );
        }

        return $skip;
    }

    /**
     * Checks if all extensions required through the "requires" annotation are loaded. Returns
     * true if one or more extensions are NOT loaded, false otherwise.
     *
     * @param string $message
     * @return bool
     */
    private function doMissingRequiresCheck( &$message ): bool {
        $requires = $this->test_case->getAttribute( "requires" );

        if ( $requires === false ) {
            return false;
        }

        // Split the "requires" annotation on "comma"
        $required_extensions = explode( ",", $requires );

        // Trim the whitespaces from the resulting items
        $required_extensions = array_map( "trim", $required_extensions );

        // Filter out all empty items
        $required_extensions = array_filter( $required_extensions, function( string $item ): bool {
            return !empty( $item );
        } );

        $er = \ExtensionRegistry::getInstance();

        // Filter out all installed extensions
        $missing_extensions = array_filter( $required_extensions, function ( string $item ) use ( $er ): bool {
            return !$er->isLoaded( $item );
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
     * @param $message
     * @return bool
     */
    private function doSkipAnnotationCheck( &$message ): bool {
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
     * @param $message
     * @return bool
     */
    private function doInvalidCoversCheck( &$message ): bool {
        if ( !$this->test_case->getCovers() ) {
            // This test case does not have a "covers" annotation
            return false;
        }

        $title = \Title::newFromText( $this->test_case->getCovers(), NS_TEMPLATE );

        if ( !$title instanceof \Title || !$title->exists() ) {
            $message = wfMessage( "mwunit-invalid-covers-annotation" )->plain();
            return true;
        }

        return false;
    }

    /**
     * Checks if the template specified in "covers" is covered and returns true if the test should
     * be marked as risky, false otherwise.
     */
    private function doCoversCheck(): bool {
        if ( !$this->test_case->getCovers() ) {
            // This test case does not have a "covers" annotation
            return false;
        }

        try {
            $strict_coverage = MediaWikiServices::getInstance()->getMainConfig()->get( 'MWUnitStrictCoverage' );
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

        return !in_array( strtolower( $this->test_case->getCovers() ), $this->test_run->getUsedTemplates() );
    }

    /**
     * Backs up globals.
     */
    private function backupGlobals() {
        $option = MediaWikiServices::getInstance()->getMainConfig()->get( 'MWUnitBackupGlobals' );

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
            $option = MediaWikiServices::getInstance()->getMainConfig()->get( 'MWUnitBackupGlobals' );
        } catch( \ConfigException $e ) {
            $option = true;
        }

        if ( $option ) {
            if ( !isset( $this->globals ) ) {
                // This execution path should never be reached, because restoreGlobals() should only be called
                // if $this->globals is available.
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
     * Serializes the current User from RequestContext and stores the result in a class variable.
     */
    private function backupUser() {
        // We serialize the user to dereference (deep clone) it
        $this->user = serialize( RequestContext::getMain()->getUser() );
    }

    /**
     * Deserializes the backed up User object and restores the User object globally.
     */
    private function restoreUser() {
        $this->setUser( unserialize( $this->user ) );
    }

    /**
     * Sets the User object globally. This is used to mock other users while running a certain test. The $wgUser
     * global will and the RequestContext user will be replaced with the given user.
     *
     * @param User $user
     */
    private function setUser( User $user ) {
        RequestContext::getMain()->setUser( $user );
        MediaWikiServices::getInstance()->getParser()->setUser( $user );

        global $wgParser;
        $wgParser->setUser( $user );

        // For extensions still using the old $wgUser variable
        global $wgUser;
        $wgUser = $user;
    }

    /**
     * Returns true if and only if the current logged in user is allowed to mock other
     * users while running a test.
     *
     * @return bool
     */
    private function canMockUsers(): bool {
        try {
            $mocking_allowed = MediaWikiServices::getInstance()->getMainConfig()->get( 'MWUnitAllowRunningTestAsOtherUser' );
        } catch( \ConfigException $e ) {
            $mocking_allowed = false;
        }

        if ( !$mocking_allowed ) {
            // Mocking users is disabled
            return false;
        }

        return in_array( 'mwunit-mock-user', RequestContext::getMain()->getUser()->getRights() );
    }

    /**
     * Returns the context to use, or false on failure.
     *
     * @return string|User|false|null
     */
    private function getContext() {
        $context_option = $this->test_case->getAttribute( 'context' );
        $user_option    = $this->test_case->getAttribute( 'user' );

        switch ( $context_option ) {
            case 'canonical':
            case false:
                global $wgVersion;

                return version_compare( $wgVersion, '1.32', '<' ) ? null : 'canonical';
            case 'user':
                if ( !$user_option ) {
                    return RequestContext::getMain()->getUser();
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
     * Checks whether or not the test case performed any assertions. Returns true if and only if
     * the test should be marked as Risky for not contains any assertions.
     *
     * @return bool
     */
    private function doAssertionCheck() {
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

        return $this->test_run->getAssertionCount() === 0;
    }

    /**
     * Returns the parser object that needs to be used for parsing. This function takes
     * the parser from MediaWikiServices and resets most of the state in the parser, but
     * keeps loaded parser hooks intact (otherwise "setup" would break).
     *
     * @return Parser
     */
    private function getParser(): Parser {
        $parser = MediaWikiServices::getInstance()->getParser();

        $parser->mAutonumber                = 0;
        $parser->mIncludeCount              = [];
        $parser->mRevisionObject            = null;
        $parser->mRevisionTimestamp         = null;
        $parser->mRevisionId                = null;
        $parser->mRevisionUser              = null;
        $parser->mRevisionSize              = null;
        $parser->mVarCache                  = [];
        $parser->mUser                      = null;
        $parser->mLangLinkLanguages         = [];
        $parser->currentRevisionCache       = null;
        $parser->mTplRedirCache             = null;
        $parser->mTplDomCache               = [];
        $parser->mIncludeSizes              = [ 'post-expand' => 0, 'arg' => 0 ];
        $parser->mPPNodeCount               = 0;
        $parser->mGeneratedPPNodeCount      = 0;
        $parser->mHighestExpansionDepth     = 0;
        $parser->mDefaultSort               = false;
        $parser->mHeadings                  = [];
        $parser->mDoubleUnderscores         = [];
        $parser->mExpensiveFunctionCount    = 0;

        return $parser;
    }
}
