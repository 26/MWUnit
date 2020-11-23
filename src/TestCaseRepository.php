<?php

namespace MWUnit;

use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\ResultWrapper;
use Title;

class TestCaseRepository {
    protected static $instance = null;

    /**
     * TestCaseRepository constructor.
     */
    private function __construct() {
        // TODO: Do not make this a singleton, but make it injectable instead
    }

    /**
     * @return TestCaseRepository
     */
    public static function getInstance(): TestCaseRepository {
	    if ( self::$instance === null  ) {
	        self::$instance = new TestCaseRepository();
        }

	    return self::$instance;
    }

    /**
     * Stores the given TestClass in the database.
     *
     * @param TestClass $test_class
     */
    public function registerTestClass( TestClass $test_class ) {
        // TODO: Make this func
    }

	/**
	 * Removes all test cases on a page from the database.
	 *
	 * @param int $article_id The article ID of the page from which the tests should be deregistered.
	 */
	public function deregisterTestsOnPage( int $article_id ) {
		$database = wfGetDb( DB_MASTER );
		$database->delete(
			'mwunit_tests',
            [ 'article_id' => $article_id ]
		);
	}

	/**
	 * Returns true if and only if the given Title object exists, is a template and has tests written for it.
	 *
	 * @param Title $title
	 * @return bool
	 */
	public function isTemplateCovered( Title $title ): bool {
		if ( !$title->exists() ) {
		    return false;
		}

		if ( $title->getNamespace() !== NS_TEMPLATE ) {
		    return false;
		}

		$template_name = $title->getText();

		return wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'article_id' ],
			[ 'covers' => $template_name ],
            __METHOD__
		)->numRows() > 0;
	}
}