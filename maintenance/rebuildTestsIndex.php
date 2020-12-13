<?php

namespace MWUnit\Maintenance;

use MWUnit\Exception\InvalidTestPageException;
use MWUnit\TestClass;
use Revision;
use Wikimedia\Rdbms\IResultWrapper;

error_reporting( 0 );

/**
 * Load the required class
 */
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

class RebuildTestsIndex extends \Maintenance {
	/**
	 * @var int
	 */
	private $done = 0;

	/**
	 * @var int
	 */
	private $total;

	/**
	 * RebuildTestsIndex constructor.
	 *
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();

		$this->addOption( 'quick', 'Skip the 5 second countdown before starting' );

		$this->addDescription( 'Rebuilds the index of tests by reparsing all pages in the "Test" namespace.' );
		$this->requireExtension( "MWUnit" );
	}

	/**
	 * @inheritDoc
	 *
	 * @throws \MWException
	 * @throws \MalformedTitleException
	 */
	public function execute() {
		// Give the user some time to abort the rebuild
		if ( !$this->getOption( 'quick', false ) ) {
			$this->output( "Abort the rebuild with control-c in the next five seconds... " );
			$this->countDown( 5 );
		}

		$this->output( "\n" );
		$this->output( "Rebuilding test indices ...\n" );
		$this->output( "\t... deleting broken indices ...\n" );

		// Delete all current records in the testcase table, since they are assumed to be bad
		$this->deleteIndices();

		// Fetch all pages in the Test namespace
		$this->output( "\t... fetching test pages ...\n" );
		$pages = $this->fetchTestPages();

		$this->total = $pages->numRows();
		$this->showProgress();

		// Loop over all pages in the Test namespace and register them
		foreach ( $pages as $page ) {
			$title = \Title::newFromTextThrow( $page->page_title, (int)$page->page_namespace );
			$wikipage = \WikiPage::factory( $title );

			$content = $wikipage->getContent( Revision::FOR_THIS_USER );
			$wikitext = $wikipage->getContentHandler()->serializeContent( $content );

			try {
				$test_class = TestClass::newFromWikitext( $wikitext, $wikipage->getTitle() );
			} catch ( InvalidTestPageException $e ) {
				$this->done++;
				$this->showProgress();

				continue;
			}

			$test_class->doUpdate();

			$this->done++;
			$this->showProgress();
		}

		$this->output( "\n" );
	}

	/**
	 * Returns an IResultWrapper object of all pages in the Test namespace.
	 *
	 * @return IResultWrapper
	 */
	private function fetchTestPages(): IResultWrapper {
		$dbr = wfGetDB( DB_REPLICA );
		return $dbr->select(
			'page',
			[ 'page_namespace', 'page_title' ],
			[
				'page_namespace' => NS_TEST,
			],
			__METHOD__
		);
	}

	/**
	 * Shows the rebuilding progress dynamically.
	 */
	private function showProgress() {
		$this->output(
			"\r\t... rebuilding test indices\t\t {$this->getProgress()}% ({$this->getDone()}/{$this->getTotal()})"
		);
	}

	/**
	 * Returns the percentage of test indices rebuilt.
	 *
	 * @return string
	 */
	private function getProgress() {
		return (string)( $this->getTotal() == 0 ?
			100 :
			floor( ( $this->getDone() / $this->getTotal() ) * 100 ) );
	}

	/**
	 * Returns the number of indices rebuilt.
	 *
	 * @return int
	 */
	private function getDone() {
		return $this->done;
	}

	/**
	 * Returns the total number of indices that have to be rebuild.
	 *
	 * @return int
	 */
	private function getTotal() {
		return $this->total;
	}

	/**
	 * Deletes all test page records from the database.
	 */
	private function deleteIndices() {
		$dbr = wfGetDB( DB_MASTER );
		$dbr->delete(
			'mwunit_tests',
			'*'
		);
	}
}

$maintClass = RebuildTestsIndex::class;
require_once RUN_MAINTENANCE_IF_MAIN;
