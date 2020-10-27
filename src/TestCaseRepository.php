<?php

namespace MWUnit;

use MWUnit\ParserFunction\TestCaseParserTag;
use MWUnit\Exception\MWUnitException;
use MWUnit\Exception\TestCaseRegistrationException;
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
     * Registers the given test cases to the database. This is used to index test cases via the MWUnit special page and
     * the maintenance script. It registers the name of the test case, the group it is in and the page on which
     * the test is located.
     *
     * @param Title $title
     * @param array $tests
     * @throws \FatalError
     * @throws \MWException
     * @throws \ConfigException
     */
	public function registerTests( \Title $title, array $tests ) {
        $article_id = $title->getArticleID();

		if ( !$title->exists() || $article_id === 0 ) {
            // This page has not yet been created.
            return;
        }

		$hook = \Hooks::run( 'MWUnitBeforeRegisterTestCases', [ &$title, &$tests ] );
		if ( $hook === false ) {
			return;
		}

        $registered = [];
        $database = wfGetDb( DB_MASTER );

		foreach ( $tests as $test ) {
		    $attributes = $test['attributes'];

            if ( !MWUnit::areAttributesValid( $attributes ) ) {
                continue;
            }

		    $name = $attributes['name'];
		    $group = $attributes['group'];
		    $covers = $attributes['covers'] ?? '';

		    $tracking_name = $name . $group;

		    if ( in_array( $tracking_name, $registered ) ) {
		        continue;
            }

		    $registered[] = $tracking_name;

            $fields = [
                'article_id' => $article_id,
                'test_group' => $group,
                'test_name'  => $name,
                'covers'     => $covers
            ];

            MWUnit::getLogger()->notice( "Registering testcase {testcase}", [
                "testcase" => $name
            ] );

            try {
                $database->insert( 'mwunit_tests', $fields );

                MWUnit::getLogger()->debug( "Registered testcase {testcase}", [
                    "testcase" => $name
                ] );
            } catch( \Exception $e ) {
                MWUnit::getLogger()->debug( "Unable to register testcase {testcase}", [
                    "testcase" => $name
                ] );
            }
        }
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
	 * Returns true if and only if the given test group name exists.
	 *
	 * @param string $test_group
	 * @return bool
	 */
	public function doesTestGroupExist( string $test_group ) {
		return wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'test_name' ],
			[ 'test_group' => $test_group ],
            __METHOD__
		)->numRows() > 0;
	}

    /**
     * Returns a TestSuite of tests in the given group.
     *
     * @param string $test_group
     * @return bool|object|IResultWrapper|ResultWrapper
     */
	public function getTestsFromGroup( string $test_group ) {
		return wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'article_id', 'test_name', 'test_group', 'covers' ],
			[ 'test_group' => $test_group ],
            __METHOD__,
			'DISTINCT'
		);
	}

    /**
     * Returns a TestSuite of tests on the given page corresponding to the given Title object.
     *
     * @param Title $title
     * @return bool|object|IResultWrapper|ResultWrapper
     */
	public function getTestsFromTitle( Title $title ) {
		$article_id = $title->getArticleID();
		return wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'article_id', 'test_name', 'test_group', 'covers' ],
			[ 'article_id' => (int)$article_id ],
            __METHOD__,
			'DISTINCT'
		);
	}

    /**
     * Returns a list of tests that cover the given Title object. The given page must be an existing template,
     * else an empty TestSuite is returned.
     *
     * @param string $title
     * @return bool|object|IResultWrapper|ResultWrapper
     */
	public function getTestsCoveringTemplate( string $title ) {
	    $title = \Title::newFromText( $title, NS_TEMPLATE );

	    if ( !$title instanceof \Title ) {
	        return false;
        }

		if ( !$title->exists() ) {
            return false;
		}

		$template_name = $title->getText();
		return wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'article_id', 'test_name', 'test_group', 'covers' ],
			[ 'covers' => $template_name ],
            __METHOD__
		);
	}

    /**
     * @param string|false $test_name
     * @return false|DatabaseTestCase
     */
	public function getTestCaseFromTestName( string $test_name ) {
        list ( $page_name, $name ) = explode( "::", $test_name );

        $title = \Title::newFromText( $page_name, NS_TEST );

        if ( !$title instanceof \Title || !$title->exists() ) {
            return false;
        }

        $result = wfGetDb( DB_REPLICA )->select(
            'mwunit_tests',
            [ 'article_id', 'test_name', 'test_group', 'covers' ],
            [ 'article_id' => $title->getArticleID(), 'test_name' => $name ],
            __METHOD__,
            'DISTINCT'
        );

        if ( $result->numRows() < 1 ) {
            return false;
        }

        return DatabaseTestCase::newFromRow( $result->current() );
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