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
    public function __construct() {
        parent::__construct();

        $this->addOption( 'quick', 'Skip the 5 second countdown before starting' );

        $this->addDescription( 'Updates the content model for all pages in the Test namespace. May be required after updating to a newer version of MWUnit.' );
        $this->requireExtension( "MWUnit" );
    }

    public function execute() {

    }
}

$maintClass = UpdateContentModels::class;
require_once RUN_MAINTENANCE_IF_MAIN;