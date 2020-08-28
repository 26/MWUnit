<?php

namespace MWUnit\Special;

use MWUnit\Exception\RebuildRequiredException;
use MWUnit\Special\UI\MWUnitFormUI;
use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use MWUnit\Runner\TestSuiteRunner;
use MWUnit\TestSuite;

/**
 * Class SpecialMWUnit
 *
 * @package MWUnit\Special
 */
class SpecialMWUnit extends \SpecialPage {
    /**
	 * SpecialMWUnit constructor.
	 * @throws \UserNotLoggedIn|\ConfigException
     */
	public function __construct() {
		parent::__construct( "MWUnit", "mwunit-runtests", true );
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
	 * @throws \MWException
	 */
	public function execute( $subpage ) {
		$this->checkPermissions();

		if ( $this->shouldRun() ) {
		    try {
                $this->runTests();
            } catch( MWUnitException $e ) {
                $ui = new UI\ExceptionUI( $e, $this->getOutput(), $this->getLinkRenderer() );
                $ui->execute();
            }

		    return;
        }

        $ui = new UI\FormUI( $this->getOutput(), $this->getLinkRenderer() );
		$ui->execute();
	}

    /**
     * @return bool
     * @throws MWUnitException
     */
	private function runTests(): bool {
	    $request = $this->getRequest();

		if ( $request->getVal( 'unitTestCoverTemplate' ) ) {
            $covers = $request->getVal( 'unitTestCoverTemplate' );
            $test_suite = TestSuite::newFromCovers( $covers );
		} elseif ( $request->getVal( 'unitTestIndividual' ) ) {
		    $test_case = $request->getVal( 'unitTestIndividual' );
		    $test_suite = TestSuite::newFromText( $test_case );
        } elseif ( $request->getVal( 'unitTestGroup' ) ) {
		    $test_case = $request->getVal( 'unitTestGroup' );
		    $test_suite = TestSuite::newFromGroup( $test_case );
        } else {
			// Run the specified page
			$title = \Title::newFromText( $this->getRequest()->getVal( 'unitTestPage' ) );

			if ( !$title instanceof \Title || !$title->exists() ) {
			    throw new RebuildRequiredException( 'mwunit-rebuild-required' );
			}

			$test_suite = TestSuite::newFromTitle( $title );
		}

		$runner = new TestSuiteRunner( $test_suite, null );
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
