<?php

namespace MWUnit\Maintenance;

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

class UpdateContentModels extends \Maintenance {
	private $done = 0;
	private $total;

	public function __construct() {
		parent::__construct();

		$this->addOption( 'quick', 'Skip the 5 second countdown before starting' );

		$this->addDescription(
			'Updates the content model for all pages in the Test namespace. ' .
			'May be required after updating to a newer version of MWUnit.'
		);
		$this->requireExtension( "MWUnit" );
	}

	/**
	 * @throws \MWException
	 * @throws \MWUnknownContentModelException
	 * @throws \MalformedTitleException
	 */
	public function execute() {
		if ( !$this->getOption( 'quick', false ) ) {
			$this->output( "Abort the update with control-c in the next five seconds... " );
			$this->countDown( 5 );
		}

		$this->output( "\n" );
		$this->output( "Updating content models ...\n" );
		$this->output( "\t... fetching test pages ...\n" );

		// Get all pages currently in the Test namespace
		$pages = $this->fetchTestPages();

		$this->total = $pages->numRows();
		$this->showProgress();

		foreach ( $pages as $page ) {
			// Create a new Revision object
			$title = \Title::newFromTextThrow( $page->page_title, (int)$page->page_namespace );
			$wikipage = \WikiPage::factory( $title );
			$revision = $wikipage->getRevision() ?: false;

			// Create a new Content object with the right content model
			$new_content = $revision instanceof \Revision ?
				\ContentHandler::makeContent( $revision->getContent()->serialize(), $title, CONTENT_MODEL_TEST ) :
				\ContentHandler::getForModelID( CONTENT_MODEL_TEST )->makeEmptyContent();

			// Flag the edit appropriately
			$flags = $revision instanceof \Revision ? EDIT_UPDATE : EDIT_NEW;
			$flags |= EDIT_INTERNAL;

			// Edit the Content object of the WikiPage
			$wikipage->doEditContent(
				$new_content,
				"",
				$flags
			);

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
			"\r\t... updating content models\t\t {$this->getProgress()}% ({$this->getDone()}/{$this->getTotal()})"
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
}

$maintClass = UpdateContentModels::class;
require_once RUN_MAINTENANCE_IF_MAIN;
