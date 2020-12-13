<?php

namespace MWUnit\Maintenance;

use MWUnit\Exception\MWUnitException;
use MWUnit\Runner\TestSuiteRunner;
use MWUnit\TestSuite;

error_reporting( 0 );

/**
 * Load the required class
 */
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

require_once __DIR__ . "/includes/MWUnitResultPrinter.php";
require_once __DIR__ . "/includes/TestDoxResultPrinter.php";

class RunTests extends \Maintenance {
	const MUTUALLY_EXCLUSIVE_OPTIONS = [ // phpcs:ignore
		"group",
		"test",
		"testsuite",
		"version",
		"list-groups",
		"list-pages",
		"list-tests"
	];

	/**
	 * RunTests constructor.
	 *
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Command-line test runner for MWUnit.' );

		$this->addOption( 'group', 'Only run tests from the specified group', false, true, 'g' );
		$this->addOption( 'test', 'Only run the specified test', false, true, 't' );
		$this->addOption( 'page', 'Filter which test page to run', false, true, 'p' );
		$this->addOption(
			'covers',
			'Only run tests that cover the specified template (without namespace)',
			false,
			true,
			'c'
		);
		$this->addOption( 'list-groups', 'List available test groups' );
		$this->addOption( 'list-pages', 'List available test pages' );
		$this->addOption( 'list-tests', 'List available tests' );
		$this->addOption( 'version', 'Prints the version' );
		$this->addOption( 'no-progress', 'Do not display progress' );
		$this->addOption( 'columns', 'Number of columns to use for progress output', false, true );
		$this->addOption( 'testdox', 'Report test execution progress in TestDox format', false, false, 'd' );

		$this->requireExtension( 'MWUnit' );
	}

	/**
	 * @inheritDoc
	 *
	 * @throws MWUnitException|\ConfigException
	 */
	public function execute() {
		$this->outputIntro();

		if ( $this->getOption( 'version' ) === 1 || count( $this->orderedOptions ) < 1 ) {
			// The intro already contains the version; exit here
			return true;
		}

		$this->output( "\n" );

		// Check if the given parameters are not mutually exclusive
		$this->checkMutuallyExclusiveOptions();

		if ( $this->getOption( 'list-groups' ) === 1 ) {
			$this->listGroups();
			return true;
		}

		if ( $this->getOption( 'list-pages' ) === 1 ) {
			$this->listPages();
			return true;
		}

		if ( $this->getOption( 'list-tests' ) === 1 ) {
			$this->listTests();
			return true;
		}

		$test_suite = $this->getTests();

		if ( count( $test_suite ) === 0 ) {
			$this->fatalError( 'No tests to run.' );
		}

		$result_printer = $this->getResultPrinter();

		$test_runner = TestSuiteRunner::newFromTestSuite( $test_suite, [ $result_printer, "testCompletionCallback" ] );
		$test_runner->run();

		$result_printer->outputTestResults( $test_runner );

		return true;
	}

	private function checkMutuallyExclusiveOptions() {
		$set = 0;
		foreach ( self::MUTUALLY_EXCLUSIVE_OPTIONS as $option ) {
			$set += $this->hasOption( $option );
		}

		if ( $set > 1 ) {
			$this->output( "Invalid combination of options. The following options are mutually exclusive:\n\n" );

			foreach ( self::MUTUALLY_EXCLUSIVE_OPTIONS as $option ) {
				$this->output( "* $option\n" );
			}

			$this->fatalError( "\nYou may supply at most one of these options.\n" );
		}
	}

	private function listGroups() {
		$database = wfGetDb( DB_REPLICA );
		$result = $database->select(
			'mwunit_tests',
			'test_group',
			[],
			'Database::select',
			'DISTINCT'
		);

		$row_count = $result->numRows();

		$descriptor = [];
		for ( $i = 0; $i < $row_count; $i++ ) {
			$row = $result->current();
			$descriptor[] = $row->test_group;

			$result->next();
		}

		$this->output( "The following groups are available:\n" );

		foreach ( $descriptor as $group ) {
			$this->output( "* $group\n" );
		}

		$this->output( "\n" );
	}

	private function listTests() {
		$database = wfGetDb( DB_REPLICA );
		$result = $database->select(
			'mwunit_tests',
			[ 'article_id', 'test_name' ],
			[],
			'Database::select',
			'DISTINCT'
		);

		$row_count = $result->numRows();

		$descriptor = [];
		for ( $i = 0; $i < $row_count; $i++ ) {
			$row = $result->current();
			$test_identifier = \Title::newFromID( $row->article_id )->getText() . "::" . $row->test_name;
			$descriptor[] = $test_identifier;
			$result->next();
		}

		$this->output( "The following tests are available:\n" );

		foreach ( $descriptor as $test ) {
			$this->output( "* $test\n" );
		}

		$this->output( "\n" );
	}

	private function listPages() {
		$database = wfGetDb( DB_REPLICA );
		$result = $database->select(
			'mwunit_tests',
			[ 'article_id' ],
			[],
			'Database::select',
			'DISTINCT'
		);

		$row_count = $result->numRows();

		$descriptor = [];
		for ( $i = 0; $i < $row_count; $i++ ) {
			$row = $result->current();
			$descriptor[] = \Title::newFromID( $row->article_id )->getText();
			$result->next();
		}

		$this->output( "The following pages are available:\n" );

		foreach ( $descriptor as $suite ) {
			$this->output( "* $suite\n" );
		}

		$this->output( "\n" );
	}

	/**
	 * Returns the TestSuite object to run for the given CLI arguments.
	 *
	 * @return TestSuite
	 * @throws MWUnitException
	 */
	private function getTests(): TestSuite {
		$group = $this->getOption( 'group', false );
		if ( $group !== false ) {
			// Run group
			return TestSuite::newFromGroup( $group );
		}

		$page = $this->getOption( 'page', false );
		if ( $page !== false ) {
			// Run testsuite
			$title = \Title::newFromText( $page, NS_TEST );

			if ( $title === null || $title === false || !$title->exists() ) {
				$this->fatalError( "The given page '$page' does not exist." );
			}

			return TestSuite::newFromTitle( $title );
		}

		$covers = $this->getOption( 'covers', false );
		if ( $covers !== false ) {
			// Run tests covering template
			$title = \Title::newFromText( $covers, NS_TEMPLATE );

			if ( $title === null || $title === false || !$title->exists() ) {
				$this->fatalError( "The given template '$covers' does not exist." );
			}

			return TestSuite::newFromCovers( $covers );
		}

		$test = $this->getOption( 'test', false );
		if ( $test !== false ) {
			if ( strpos( $test, '::' ) === false ) {
				$this->fatalError( "The test name '$test' is invalid." );
			}

			return TestSuite::newFromText( $test );
		}

		return TestSuite::newEmpty();
	}

	/**
	 * Outputs the "intro" text.
	 */
	private function outputIntro() {
		$version = \ExtensionRegistry::getInstance()->getAllThings()['MWUnit']['version'] ?? null;
		$this->output( "MWUnit $version by Marijn van Wezel and contributors.\n" );
	}

	/**
	 * Return the appropriate ResultPrinter for the given CLI arguments.
	 *
	 * @return CommandLineResultPrinter
	 * @throws \ConfigException
	 */
	private function getResultPrinter() {
		if ( $this->hasOption( "testdox" ) || $this->getConfig()->get( "MWUnitDefaultTestDox" ) === true ) {
			return new TestDoxResultPrinter();
		}

		return new MWUnitResultPrinter(
			(int)$this->getOption( 'columns', 48 ),
			$this->hasOption( 'no-progress' )
		);
	}
}

$maintClass = RunTests::class;
require_once RUN_MAINTENANCE_IF_MAIN;
