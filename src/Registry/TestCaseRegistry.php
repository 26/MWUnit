<?php

namespace MWUnit\Registry;

use MWUnit\Exception\MWUnitException;
use MWUnit\Exception\TestCaseRegistrationException;
use MWUnit\MWUnit;
use MWUnit\ConcreteTestCase;
use MWUnit\TestCase;
use MWUnit\TestSuite;
use Title;

class TestCaseRegistry {
	/**
	 * @var array Names of the test register initialisations in the current run of the parser.
	 */
	private static $init_registered_tests = [];

	/**
	 * Registers a test case to the database. This is used to index test cases via the MWUnit special page and
	 * the maintenance script. It registers the name of the test case, the group it is in and the page on which
	 * the test is located.
	 *
	 * @param ConcreteTestCase $test_case
	 * @throws MWUnitException
	 * @throws TestCaseRegistrationException
	 * @throws \FatalError
	 * @throws \MWException
	 */
	public static function register( ConcreteTestCase $test_case ) {
		if ( !$test_case->getTitle()->exists() ) {
			// This page has not yet been created.
			return;
		}

		$hook = \Hooks::run( 'MWUnitBeforeRegisterTestCase', [ &$test_case ] );
		if ( $hook === false ) {
			return;
		}

		self::$init_registered_tests[] = $test_case->__toString();

		$registered = self::isTestRegistered( $test_case );

		if ( $registered === true ) {
			MWUnit::getLogger()->notice( "Did not register testcase {testcase} because it was already registered", [
				"testcase" => $test_case->__toString()
			] );

			// This test has already been registered on this page
			throw new TestCaseRegistrationException(
				'mwunit-duplicate-test',
				[ htmlspecialchars( $test_case->getName() ) ]
			);
		}

		if ( $registered === null ) {
			return;
		}

		$fields = [
			'article_id' => $test_case->getTitle()->getArticleID(),
			'test_group' => $test_case->getGroup(),
			'test_name'  => $test_case->getName()
		];

		if ( $test_case->getOption( 'covers' ) ) {
			$fields[ 'covers' ] = $test_case->getOption( 'covers' );
		}

		MWUnit::getLogger()->notice( "Registering testcase {testcase}", [
			"testcase" => $test_case->__toString()
		] );

		$database = wfGetDb( DB_MASTER );
		$database->insert( 'mwunit_tests', $fields );

		MWUnit::getLogger()->debug( "Registered testcase {testcase}", [
			"testcase" => $test_case->__toString()
		] );
	}

	/**
	 * Removes all test cases on a page from the database.
	 *
	 * @param int $article_id The article ID of the page from which the tests should be deregistered.
	 */
	public static function deregisterTests( int $article_id ) {
		$database = wfGetDb( DB_MASTER );
		$database->delete(
			'mwunit_tests',
            [ 'article_id' => $article_id ]
		);
	}

	/**
	 * Returns true if and only if the given test exists.
	 *
	 * @param string $page_title
	 * @param string $test_name
	 * @return bool
	 */
	public static function testExists( string $page_title, string $test_name ) {
		$title = Title::newFromText( $page_title );

		if ( !$title instanceof Title ) {
		    return false;
		}

		return wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'test_name' ],
			[ 'article_id' => $title->getArticleID(), 'test_name' => $test_name ],
            __METHOD__
		)->numRows() > 0;
	}

	/**
	 * Returns true if and only if the given test group name exists.
	 *
	 * @param string $test_group
	 * @return bool
	 */
	public static function testGroupExists( string $test_group ) {
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
	 * @throws MWUnitException
	 */
	public static function getTestsFromGroup( string $test_group ) {
		return wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'article_id', 'test_name', 'test_group' ],
			[ 'test_group' => $test_group ],
            __METHOD__,
			'DISTINCT'
		);
	}

    /**
     * Returns a TestSuite of tests on the given page corresponding to the given Title object.
     *
     * @param Title $title
     */
	public static function getTestsFromTitle( Title $title ) {
		$article_id = $title->getArticleID();
		return wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'article_id', 'test_name', 'test_group' ],
			[ 'article_id' => (int)$article_id ],
            __METHOD__,
			'DISTINCT'
		);
	}

    /**
     * Returns a TestSuite of tests that cover the given Title object. The given page must be an existing template,
     * else an empty TestSuite is returned.
     *
     * @param string $title
     */
	public static function getTestsCoveringTemplate( string $title ) {
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
			[ 'article_id', 'test_name', 'test_group' ],
			[ 'covers' => $template_name ],
            __METHOD__
		);
	}

	public static function getGroupFromTestName( string $test_name ) {
        list ( $page_name, $name ) = explode( "::", $test_name );

        $title = \Title::newFromText( $page_name, NS_TEST );

        if ( !$title instanceof \Title || !$title->exists() ) {
            return false;
        }

        $result = wfGetDb( DB_REPLICA )->select(
            'mwunit_tests',
            [ 'test_group' ],
            [ 'article_id' => $title->getArticleID(), 'test_name' => $name ],
            __METHOD__,
            'DISTINCT'
        );

        if ( $result->numRows() < 1 ) {
            return false;
        }

        return $result->current()->test_group;
    }

	/**
	 * Returns true if and only if the given Title object exists, is a template and has tests written for it.
	 *
	 * @param Title $title
	 * @return bool
	 */
	public static function isTemplateCovered( Title $title ): bool {
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

	/**
	 * Returns true if and only if the given $test_case has already been registered.
	 *
	 * @param TestCase $test_case
	 * @return bool|null True when it has already been registered, false when it has not been registered or
	 * null when we have already registered the given test, but it was not a duplicate.
	 */
	private static function isTestRegistered( TestCase $test_case ) {
		$database = wfGetDb( DB_MASTER );
		$result = $database->select(
			'mwunit_tests',
			[ 'article_id' ],
			[
				'test_name' => $test_case->getName(),
				'article_id' => $test_case->getTitle()->getArticleID()
			],
			__METHOD__
		);

		if ( $result->numRows() < 1 ) {
			return false;
		}

		if ( (int)$result->current()->article_id !== $test_case->getTitle()->getArticleID() ) {
			return false;
		}

		$init_registered_test_count = array_count_values( self::$init_registered_tests );
		$test_name = $test_case->__toString();

		if ( $init_registered_test_count[ $test_name ] < 2 ) {
			return null;
		}

		return true;
	}
}
