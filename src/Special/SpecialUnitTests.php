<?php

namespace MWUnit\Special;

use MWUnit\Exception\MWUnitException;
use MWUnit\Runner\TestSuiteRunner;
use MWUnit\TestSuite;
use PermissionsError;

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
	 * Executes the special page.
	 *
	 * @param string|null $subpage
	 * @throws PermissionsError
	 */
	public function execute( $subpage ) {
		$this->checkPermissions();

		$test_suite = $this->getTestSuite();

		if ( $test_suite === false ) {
			$ui = new UI\FormUI( $this->getOutput(), $this->getLinkRenderer() );
			$ui->execute();

			return;
		}

		try {
			$this->runTests( $test_suite );
		} catch ( MWUnitException $e ) {
			$ui = new UI\ExceptionUI( $e, $this->getOutput(), $this->getLinkRenderer() );
			$ui->execute();
		}
	}

	/**
	 * @param TestSuite $suite
	 */
	private function runTests( TestSuite $suite ) {
		$runner = TestSuiteRunner::newFromTestSuite( $suite );
		$runner->run();

		$ui = new UI\ResultUI( $runner, $this->getOutput(), $this->getLinkRenderer() );
		$ui->execute();
	}

	/**
	 * Returns the appropriate TestSuite for the request, or false on failure.
	 *
	 * @return false|TestSuite
	 */
	private function getTestSuite() {
		$request = $this->getRequest();

		$covers     = $request->getVal( 'cover' );
		$test       = $request->getVal( 'test' );
		$group      = $request->getVal( 'group' );
		$title      = $request->getVal( 'page' );

		try {
			if ( !empty( $covers ) ) {
				return TestSuite::newFromCovers( $covers );
			}

			if ( !empty( $test ) ) {
				return TestSuite::newFromText( $test );
			}

			if ( !empty( $group ) ) {
				return TestSuite::newFromGroup( $group );
			}

			if ( !empty( $title ) ) {
				// Run the specified page
				$title_object = \Title::newFromText( $title );

				if ( !$title_object instanceof \Title ) {
					return false;
				}

				return TestSuite::newFromTitle( $title_object );
			}
		} catch ( MWUnitException $e ) {
			return false;
		}

		return false;
	}
}
