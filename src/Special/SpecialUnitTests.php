<?php

namespace MWUnit\Special;

use MWUnit\Exception\MWUnitException;
use MWUnit\Runner\TestSuiteRunner;
use MWUnit\Store\TestRunStore;
use MWUnit\TestSuite;

/**
 * Class SpecialMWUnit
 *
 * @package MWUnit\Special
 */
class SpecialUnitTests extends \SpecialPage {
    /**
	 * SpecialMWUnit constructor.
	 * @throws \UserNotLoggedIn|\ConfigException
     */
	public function __construct() {
		parent::__construct( "UnitTests", "mwunit-runtests", true );
		parent::requireLogin();

		set_time_limit( $this->getConfig()->get( 'MWUnitMaxTestExecutionTime' ) );
	}

	/**
	 * Returns part of the message key for the category to which this special page belongs.
	 *
	 * @return string
	 */
	public function getGroupName() {
		return 'mwunit';
	}

    /**
     * @param string|null $subpage
     * @throws \PermissionsError
     */
	public function execute( $subpage ) {
		$this->checkPermissions();

		if ( !$this->shouldRun() ) {
            $ui = new UI\FormUI( $this->getOutput(), $this->getLinkRenderer() );
            $ui->execute();

            return;
        }

        try {
            $result = $this->runTests();

            if ( !$result ) {
                $ui = new UI\FormUI( $this->getOutput(), $this->getLinkRenderer() );
                $ui->execute();
            }
        } catch( MWUnitException $e ) {
            $ui = new UI\ExceptionUI( $e, $this->getOutput(), $this->getLinkRenderer() );
            $ui->execute();
        }
	}

    /**
     * @return bool
     * @throws MWUnitException
     */
	private function runTests(): bool {
	    $request = $this->getRequest();

	    $covers     = $request->getVal( 'unitTestCoverTemplate' );
        $individual = $request->getVal( 'unitTestIndividual' );;
        $group      = $request->getVal( 'unitTestGroup' );
        $title      = $request->getVal( 'unitTestPage' );

		if ( !empty( $covers ) ) {
            $test_suite = TestSuite::newFromCovers( $covers );
		} elseif ( !empty( $individual ) ) {
		    $test_suite = TestSuite::newFromText( $individual );
        } elseif ( !empty( $group ) ) {
		    $test_suite = TestSuite::newFromGroup( $group );
        } elseif ( !empty( $title ) ) {
			// Run the specified page
			$title_object = \Title::newFromText( $title );

			if ( !$title_object instanceof \Title ) {
			    return false;
            }

			$test_suite = TestSuite::newFromTitle( $title_object );
		} else {
		    return false;
        }

		$test_run_store = new TestRunStore();

		$runner = new TestSuiteRunner( $test_suite, $test_run_store );
		$runner->run();

        $ui = new UI\ResultUI( $runner, $this->getOutput(), $this->getLinkRenderer() );
        $ui->execute();

        return true;
	}

    /**
     * Returns true if and only if tests should be run.
     *
     * @return bool
     */
    private function shouldRun() {
	    $request = $this->getRequest();

	    return $request->getVal( "unitTestPage", false ) ||
            $request->getVal( "unitTestCoverTemplate", false ) ||
            $request->getVal( "unitTestIndividual", false) ||
            $request->getVal( "unitTestGroup", false);
    }
}
