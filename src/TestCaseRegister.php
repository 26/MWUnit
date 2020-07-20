<?php

namespace MWUnit;

class TestCaseRegister {
	/**
	 * @var array Names of the test register initialisations in the current run of the parser.
	 */
	private static $init_registered_tests = [];

	/**
	 * Registers a test case to the database. This is used to index test cases via the MWUnit special page and
	 * the maintenance script. It registers the name of the test case, the group it is in and the page on which
	 * the test is located.
	 *
	 * @param TestCase $test_case
	 * @throws Exception\MWUnitException
	 * @throws Exception\TestCaseRegistrationException
	 * @throws \FatalError
	 * @throws \MWException
	 */
	public static function register( TestCase $test_case ) {
	    if ( !$test_case->getParser()->getTitle()->exists() ) {
	        // This page has not yet been created.
	        return;
        }

		$result = \Hooks::run( 'MWUnitBeforeRegisterTestCase', [ &$test_case ] );

		if ( $result === false ) {
			return;
		}

		$database = wfGetDb( DB_MASTER );
		$result = $database->select(
			'mwunit_tests',
			[
				'article_id'
			],
			[
				'test_name' => $test_case->getName(),
				'article_id' => $test_case->getParser()->getTitle()->getArticleID()
			],
			__METHOD__
		);

		self::$init_registered_tests[] = MWUnit::getCanonicalTestNameFromTestCase( $test_case );

		if ( $result->numRows() > 0 ) {
			// Do not throw an error when its the same test
			if ( (int)$result->current()->article_id === $test_case->getParser()->getTitle()->getArticleID() ) {
				$init_registered_test_count = array_count_values( self::$init_registered_tests );
				$test_name = MWUnit::getCanonicalTestName(
					$result->current()->article_id,
					$test_case->getName()
				);

				if ( $init_registered_test_count[ $test_name ] < 2 ) {
					return;
				}
			}

			// This test has already been registered on this page, or on a different page
			throw new Exception\TestCaseRegistrationException(
				'mwunit-duplicate-test',
				[ htmlspecialchars( $test_case->getName() ) ]
			);
		}

		$fields = [
			'article_id' => $test_case->getParser()->getTitle()->getArticleID(),
			'test_group' => $test_case->getGroup(),
			'test_name'  => $test_case->getName()
		];

		if ( $test_case->getOption( 'covers' ) ) {
			$fields[ 'covers' ] = $test_case->getOption( 'covers' );
		}

		$database->insert( 'mwunit_tests', $fields );
	}

    /**
     * Removes all test cases on a page from the database.
     *
     * @param integer $article_id The article ID of the page from which the tests should be deregistered.
     */
	public static function deregisterTests( int $article_id ) {
		$database = wfGetDb( DB_MASTER );
		$database->delete(
			'mwunit_tests',
			[
				'article_id' => $article_id
			]
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
		$title = \Title::newFromText( $page_title );

		if ( $title === null || $title === false ) { return false;
		}

		return wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'test_name' ],
			[ 'article_id' => $title->getArticleID(), 'test_name' => $test_name ],
			'Database::select'
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
			'Database::select'
		)->numRows() > 0;
	}

	/**
	 * Returns an array of tests in the given group.
	 *
	 * @param string $test_group
	 * @return array
	 * @throws Exception\MWUnitException
	 */
	public static function getTestsForGroup( string $test_group ) {
		$result = wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'article_id', 'test_name' ],
			[ 'test_group' => $test_group ],
			'Database::select',
			'DISTINCT'
		);

		$tests = [];
		$test_count = $result->numRows();

		for ( $i = 0; $i < $test_count; $i++ ) {
			$row = $result->current();
			$tests[ MWUnit::getCanonicalTestName( $row->article_id, $row->test_name ) ] = (int)$row->article_id;
			$result->next();
		}

		return $tests;
	}

	/**
	 * Returns an array of tests on the given page corresponding to the given Title object.
	 *
	 * @param \Title $title
	 * @return array
	 * @throws Exception\MWUnitException
	 */
	public static function getTestsFromTitle( \Title $title ): array {
		$article_id = $title->getArticleID();
		$result = wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'test_name' ],
			[ 'article_id' => (int)$article_id ],
			'Database::select',
			'DISTINCT'
		);

		$tests = [];
		$test_count = $result->numRows();

		for ( $i = 0; $i < $test_count; $i++ ) {
			$row = $result->current();
			$tests[ MWUnit::getCanonicalTestName( (int)$article_id, $row->test_name ) ] = (int)$article_id;
			$result->next();
		}

		return $tests;
	}

	/**
	 * Returns an array of tests that cover the given Title object. The given page must be an existing template,
	 * else an empty array is returned.
	 *
	 * @param \Title $title
	 * @return array
	 * @throws Exception\MWUnitException
	 */
	public static function getTestsCoveringTemplate( \Title $title ): array {
		if ( !$title->exists() ) { return [];
		}
		if ( $title->getNamespace() !== NS_TEMPLATE ) { return [];
		}

		$template_name = $title->getText();

		$result = wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'article_id', 'test_name' ],
			[ 'covers' => $template_name ],
			'Database::select'
		);

		$tests = [];
		$test_count = $result->numRows();

		for ( $i = 0; $i < $test_count; $i++ ) {
			$row = $result->current();
			$tests[ MWUnit::getCanonicalTestName( (int)$row->article_id, $row->test_name ) ] = (int)$row->article_id;
			$result->next();
		}

		return $tests;
	}

	/**
	 * Returns true if and only if the given Title object exists, is a template and has tests written for it.
	 *
	 * @param \Title $title
	 * @return bool
	 */
	public static function isTemplateCovered( \Title $title ): bool {
		if ( !$title->exists() ) { return false;
		}
		if ( $title->getNamespace() !== NS_TEMPLATE ) { return false;
		}

		$template_name = $title->getText();

		return wfGetDb( DB_REPLICA )->select(
			'mwunit_tests',
			[ 'article_id' ],
			[ 'covers' => $template_name ],
			'Database::select'
		)->numRows() > 0;
	}
}
