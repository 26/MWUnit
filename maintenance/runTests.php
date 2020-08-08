<?php

namespace MWUnit\Maintenance;

use MWUnit\Exception\MWUnitException;
use MWUnit\Registry\TestCaseRegistry;
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
		"list-suites",
		"list-tests"
	];

	/**
	 * @var bool
	 */
	private $rebuild_required;

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
		$this->addOption( 'testsuite', 'Filter which testsuite to run', false, true, 's' );
		$this->addOption(
			'covers',
			'Only run tests that cover the specified template (without namespace)',
			false,
			true,
			'c'
		);
		$this->addOption( 'list-groups', 'List available test groups' );
		$this->addOption( 'list-suites', 'List available test suites' );
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
		$version = \ExtensionRegistry::getInstance()->getAllThings()['MWUnit']['version'] ?? null;
		$this->output( "MWUnit $version by Marijn van Wezel and contributors.\n" );

		if ( $this->getOption( 'version' ) === 1 ) {
			return true;
		}

		$this->output( "\n" );
		$this->checkMutuallyExclusiveOptions();

		if ( $this->getOption( 'list-groups' ) === 1 ) {
			$this->listGroups();
			return true;
		}

		if ( $this->getOption( 'list-suites' ) === 1 ) {
			$this->listSuites();
			return true;
		}

		if ( $this->getOption( 'list-tests' ) === 1 ) {
			$this->listTests();
			return true;
		}

		$group = $this->getOption( 'group', false );
		$test = $this->getOption( 'test', false );
		$testsuite = $this->getOption( 'testsuite', false );
		$covers = $this->getOption( 'covers', false );

		if ( !$group && !$test && !$testsuite && !$covers ) {
			$this->fatalError( "No tests to run." );
		}

		$tests = $this->getTests();

		if ( count( $tests ) === 0 ) {
			$this->fatalError( 'No tests to run.' );
		}

		$interface = $this->getOption( 'testdox', 0 ) === 1 ||
					 $this->getConfig()->get( "MWUnitDefaultTestDox" ) === true ?
			new TestDoxResultPrinter() :
			new MWUnitResultPrinter(
				(int)$this->getOption( 'columns', 16 ),
				(bool)$this->getOption( 'no-progress', false )
			);

		$unit_test_runner = new TestSuiteRunner( $tests, [ $interface, "testCompletionCallback" ] );
		$unit_test_runner->run();

		$interface->outputTestResults( $unit_test_runner );

		return true;
	}

	private function checkMutuallyExclusiveOptions() {
		$set = 0;
		foreach ( self::MUTUALLY_EXCLUSIVE_OPTIONS as $option ) {
			$set += (bool)$this->getOption( $option, false );
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

	private function listSuites() {
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

		$this->output( "The following testsuites are available:\n" );

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
			if ( !TestCaseRegistry::getInstance()->testGroupExists( $group ) ) {
				$this->fatalError( "The group '$group' does not exist." );
			}

			return TestSuite::newFromGroup( $group );
		}


        $testsuite = $this->getOption( 'testsuite', false );
        if ( $testsuite !== false ) {
			// Run testsuite
			$title = \Title::newFromText( $testsuite, NS_TEST );

			if ( $title === null || $title === false || !$title->exists() ) {
				$this->fatalError( "The given testsuite '$testsuite' does not exist." );
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

		// Run test
		if ( strpos( $test, '::' ) === false ) {
			$this->fatalError( "The test name '$test' is invalid." );
		}

		return TestSuite::newFromText( $test );
	}
}

$maintClass = RunTests::class;
require_once RUN_MAINTENANCE_IF_MAIN;
