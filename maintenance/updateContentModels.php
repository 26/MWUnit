<?php

namespace MWUnit\Maintenance;

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

        $this->addDescription( 'Updates the content model for all pages in the Test namespace. May be required after updating to a newer version of MWUnit.' );
        $this->requireExtension( "MWUnit" );
    }

    public function execute() {
        if ( !$this->getOption( 'quick', false ) ) {
            $this->output( "Abort the update with control-c in the next five seconds... " );
            $this->countDown( 5 );
        }

        $this->output( "\n" );
        $this->output( "Updating content models ...\n" );
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

        $this->total = $res->numRows();
        $this->showProgress();

        for ( $i = 0; $i < $res->numRows(); $i++ ) {
            $row = $res->next();

            $title = \Title::newFromText( $row->page_title, (int)$row->page_namespace );

            $page = \WikiPage::factory( $title );
            $revision = $page->getRevision() ?: false;

            if ( $revision ) {
                $content = $revision->getContent();

                try {
                    $new_content = \ContentHandler::makeContent( $content->serialize(), $title, CONTENT_MODEL_TEST );
                } catch ( \MWException $e ) {
                    $this->fatalError( "Unable to convert content model: " . $e->getMessage() );
                    return;
                }
            } else {
                $new_content = \ContentHandler::getForModelID( CONTENT_MODEL_TEST )->makeEmptyContent();
            }

            $flags = $revision ? EDIT_UPDATE : EDIT_NEW;
            $flags |= EDIT_INTERNAL;

            try {
                $page->doEditContent(
                    $new_content,
                    "",
                    $flags
                );
            } catch ( \MWException $e ) {
                $this->fatalError( "Unable to edit content: " . $e->getMessage() );
                return;
            }

            $this->done++;
            $this->showProgress();
        }

        $this->output( "\n" );
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