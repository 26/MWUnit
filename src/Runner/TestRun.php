<?php

namespace MWUnit\Runner;

use ConfigException;
use FatalError;
use Hooks;
use MediaWiki\MediaWikiServices;
use MWException;
use MWUnit\ParserFunction\AssertionParserFunction;
use MWUnit\ParserFunction\TemplateMockParserFunction;
use MWUnit\ParserFunction\VarDumpParserFunction;
use MWUnit\Profiler;
use MWUnit\Store\TestOutputStore;
use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use MWUnit\Runner\Result\FailureTestResult;
use MWUnit\Runner\Result\RiskyTestResult;
use MWUnit\Runner\Result\SuccessTestResult;
use MWUnit\ConcreteTestCase;
use MWUnit\Runner\Result\TestResult;
use MWUnit\TestCase;
use Parser;
use ParserOptions;
use RequestContext;
use Revision;
use Title;
use User;

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
	 * The name of the template that this test case covers, or false if it does not cover a template.
	 *
	 * @var bool|string
	 */
	private $covered;

    /**
     * @var int Number of assertions used in this test run.
     */
    private $assertion_count = 0;

    /**
     * @var string The canonical name of this test.
     */
    private $test_name;

    /**
     * @var TestResult The result of this test run.
     */
    private $result;

    /**
     * @var float
     */
    private $execution_time;

    /**
     * @var TestOutputStore
     */
    private $test_output_collector;

    /**
	 * @var ConcreteTestCase
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
     * @var array Array of templates used in this test run.
     */
    private static $templates_used;

    /**
     * A clone of the parser right before it encountered the first test case. Used for strict coverage checking.
     *
     * @var Parser
     */
    private static $initial_parser;

    /**
	 * Called when the parser fetches a template. Used for strict coverage checking.
	 *
	 * @param Parser|bool $parser
	 * @param Title $title
	 * @param Revision $revision
	 * @param string|false|null &$text
	 * @param array &$deps
	 */
	public static function onParserFetchTemplate(
		$parser,
		Title $title,
		Revision $revision,
		&$text,
		array &$deps
	) {
		self::$templates_used[] = strtolower( $title->getText() );
	}

	/**
	 * TestCaseRun constructor.
	 *
	 * @param ConcreteTestCase $test_case
	 */
	public function __construct( ConcreteTestCase $test_case ) {
	    $this->test_case = $test_case;
        $this->covered   = strtolower( $test_case->getOption( 'covers' ) );
        $this->test_output_collector = new TestOutputStore;

        self::$templates_used = [];

        // Dependency injection
        AssertionParserFunction::setTestRun( $this );
        TemplateMockParserFunction::setTestRun( $this );
        VarDumpParserFunction::setTestRun( $this );
	}

    /**
     * Returns true if and only if the test has finished and a result is available.
     *
     * @return bool
     */
    public function resultAvailable(): bool {
        return isset( $this->result );
    }

    /**
     * Increments the assertion count for this run.
     */
    public function incrementAssertionCount() {
        $this->assertion_count++;
    }

    /**
     * @param string $message Localised message to use for the "risky" message.
     */
    public function setRisky( string $message ) {
        $this->result = new RiskyTestResult(
            $message,
            $this->test_case
        );
    }

    /**
     * @param string $message Localised message to use for the "failure" message.
     */
    public function setFailure( string $message ) {
        $this->result = new FailureTestResult(
            $message,
            $this->test_case
        );
    }

    /**
     * Returns the result of this test run.
     *
     * @return TestResult
     */
    public function getResult(): TestResult {
        return $this->result;
    }

    /**
     * Returns the TestOutputCollector object for this TestRun.
     *
     * @return TestOutputStore
     */
    public function getTestOutputCollector(): TestOutputStore {
        return $this->test_output_collector;
    }

    /**
     * Returns the value of the "covers" annotation, or false if no "covers" annotation is given.
     *
     * @return string|false
     */
    public function getCovered() {
        return $this->covered;
    }

    /**
     * Returns the number of assertions ran for this test case.
     *
     * @return int
     */
    public function getAssertionCount(): int {
        return $this->assertion_count;
    }

    /**
     * Returns the test case associated with this run.
     *
     * @return TestCase
     */
    public function getTestCase(): TestCase {
        return $this->test_case;
    }

    /**
     * Returns the execution time of this test.
     *
     * @return float
     */
    public function getExecutionTime(): float {
        return $this->execution_time;
    }

    /**
	 * @throws FatalError
	 * @throws MWException
	 * @throws MWUnitException
	 * @throws ConfigException
	 */
	public function runTest() {
		// Store a clone of the initial parser, so we can properly perform coverage checks, without
		// breaking fixtures and global state.
		if ( !isset( self::$initial_parser ) ) {
			self::$initial_parser = clone MediaWikiServices::getInstance()->getParser();
		}

		$this->backupUser();
        $this->backupGlobals();

        $profiler = Profiler::getInstance();
        $profiler_flag = md5( rand() );

		try {
            $context = $this->getContext();
			if ( $context === false ) {
			    return;
            }

            Hooks::run( 'MWUnitBeforeRunTestCase', [ &$this->test_case ] );

            $profiler->flag( md5( rand() ) );

            // Run test case
            MediaWikiServices::getInstance()->getParser()->parse(
                $this->test_case->getInput(),
                $this->test_case->getTitle(),
                ParserOptions::newCanonical( $context ),
                true,
                false
	        );

            $this->checkTemplateCoverage();
		} finally {
            $profiler->flag( $profiler_flag );
            $this->setExecutionTime( $profiler->getFlagExecutionTime( $profiler_flag ) );

            $this->restoreGlobals();
            $this->restoreUser();

            if ( !isset( $this->result ) ) {
                $this->setSuccess();
            }
		}
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
	 * @throws MWUnitException
	 * @throws ConfigException
	 */
	private function restoreGlobals() {
		$option = MediaWikiServices::getInstance()->getMainConfig()->get( 'MWUnitBackupGlobals' );

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
     * Checks if the template specified in "covers" is covered and marks $test_result accordingly.
     */
	private function checkTemplateCoverage() {
		$strict_coverage = MediaWikiServices::getInstance()->getMainConfig()->get(
				'MWUnitStrictCoverage'
			) && $this->test_case->getOption( 'ignorestrictcoverage' ) === false;

		if ( $this->covered && $strict_coverage && !in_array( $this->covered, self::$templates_used ) ) {
			$this->setRisky( wfMessage( 'mwunit-strict-coverage-violation' )->parse() );
		}
	}

	/**
	 * Serializes the current User from RequestContext and stores the result in a class variable.
	 */
	private function backupUser() {
		$this->user = serialize( RequestContext::getMain()->getUser() );
	}

	/**
	 * Deserializes the backed up User object and restores the User object globally.
	 */
	private function restoreUser() {
		assert( isset( $this->user ) );
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

		// For extension still using the old $wgUser variable
		global $wgUser;
		$wgUser = $user;
	}

    /**
     * Marks this test run as successful.
     */
    private function setSuccess() {
        $this->result = new SuccessTestResult(
            $this->test_case
        );
    }

    /**
     * Sets the execution time of this test.
     *
     * @param float $execution_time
     */
    private function setExecutionTime( float $execution_time ) {
        $this->execution_time = $execution_time;
    }

	/**
	 * Returns true if and only if the current logged in user is allowed to mock other
	 * users while running a test.
	 *
	 * @return bool
	 */
	private function canMockUsers(): bool {
		try {
			$allow_running_tests_as_other_user = MediaWikiServices::getInstance()
				->getMainConfig()
				->get( 'MWUnitAllowRunningTestAsOtherUser' );
		} catch ( ConfigException $exception ) {
			return false;
		}

		return $allow_running_tests_as_other_user === true &&
			in_array( 'mwunit-mock-user', RequestContext::getMain()->getUser()->getRights() );
	}

    /**
     * @return false|string|User|null
     */
    private function getContext() {
        $context_option = $this->test_case->getOption( 'context' );
        $user_option    = $this->test_case->getOption( 'user' );

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
                    $this->setRisky( wfMessage( 'mwunit-missing-permissions-mock-user' )->parse() );
                    return false;
                }

                $context = User::newFromName( $user_option );

                if ( !$context instanceof User ) {
                    $this->setRisky( wfMessage( 'mwunit-invalid-user' )->parse() );
                    return false;
                }

                $this->setUser( $context );
                return $context;
            default:
                MWUnit::getLogger()->debug( "Invalid context on {context} on {test}", [
                    'context' => $context_option,
                    'test'    => $this->test_name
                ] );

                $this->setRisky( wfMessage( 'mwunit-invalid-context' )->parse() );
                return false;
        }
    }
}
