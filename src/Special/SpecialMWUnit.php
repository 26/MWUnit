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
	 * @return string
	 */
	public function getDescription() {
		return MWUnit::isRunning() ?
			$this->msg( 'mwunit-special-result-title' )->plain() :
			$this->msg( 'mwunit-special-title' )->plain();
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

        $ui = new MWUnitFormUI( $this->getRequest(), $this->getOutput(), $this->getLinkRenderer() );
        $ui->execute();
	}

    /**
     * @return bool
     * @throws MWUnitException
     */
	private function runTests(): bool {
		if ( $this->getRequest()->getVal( 'unitTestCoverTemplate' ) ) {
            $covers = $this->getRequest()->getVal( 'unitTestCoverTemplate' );
            $test_suite = TestSuite::newFromCovers( $covers );
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
            $request->getVal( "unitTestCoverTemplate", false );
    }
}
