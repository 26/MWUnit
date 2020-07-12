<?php

namespace MWUnit\Maintenance;

/**
 * Load the required class
 */
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

class RebuildTestsIndex extends \Maintenance {
	private $done = 0;
	private $total;

	/**
	 * RebuildTestsIndex constructor.
	 *
	 * @inheritDoc
	 */
	public function __construct(){
		parent::__construct();

		$this->addOption( 'quick', 'Skip the 5 second countdown before starting' );

		$this->addDescription('Rebuilds the index of tests by reparsing all pages in the "Tests" namespace.');
		$this->requireExtension( "MWUnit" );
	}

	/**
	 * @inheritDoc
	 * @throws \MWException
	 */
	public function execute() {
		if ( !$this->getOption( 'quick', false ) ) {
			$this->output( "Abort the rebuild with control-c in the next five seconds... " );
			$this->countDown( 5 );
		}

		$this->output( "\n" );
		$this->output( "Rebuilding test indices ...\n" );
		$this->output( "\t... fetching test pages ...\n" );

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'page',
			[ 'page_namespace', 'page_title' ],
			[
				'page_namespace' => NS_TEST,
			],
			__METHOD__
		);

		global $wgVersion;
		$context = version_compare( $wgVersion, '1.32', '<' ) ? null : 'canonical';

		$this->total = $res->numRows();
		$this->showProgress();

		while ( $row = $res->next() ) {
			$title = \Title::newFromText( $row->page_title, (int)$row->page_namespace );
			$page = \WikiPage::newFromID( $title->getArticleID() );

			$parser = ( \MediaWiki\MediaWikiServices::getInstance() )->getParser();
			$parser->parse(
				\ContentHandler::getContentText( $page->getContent() ),
				$title,
				\ParserOptions::newCanonical( $context )
			);

			$this->done++;
			$this->showProgress();
		}

		$this->output( "\n" );
	}

	private function showProgress() {
		$this->output(
			"\r\t... rebuilding test indices\t\t {$this->getProgress()}% ({$this->getDone()}/{$this->getTotal()})"
		);
	}

	private function getProgress() {
		return (string)$this->getTotal() == 0 ? 100 : floor( ( $this->getDone() / $this->getTotal() ) * 100 );
	}

	private function getDone() {
		return $this->done;
	}

	private function getTotal() {
		return $this->total;
	}
}

$maintClass = RebuildTestsIndex::class;
require_once RUN_MAINTENANCE_IF_MAIN;