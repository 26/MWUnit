<?php

namespace MWUnit;

use Countable;
use Iterator;
use MWUnit\Exception\MWUnitException;
use Title;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Class TestSuite
 *
 * @package MWUnit
 */
class TestSuite implements Iterator, Countable {
	/**
	 * @var array
	 */
	public $test_classes;

	/**
	 * @var int
	 */
	private $index = 0;

	/**
	 * @param string $group
	 * @return TestSuite
	 */
	public static function newFromGroup( string $group ): TestSuite {
		$dbr = wfGetDB( DB_REPLICA );

		$db_result = $dbr->select(
			'mwunit_tests',
			[ 'article_id', 'test_name' ],
			[ 'test_group' => $group ],
			__METHOD__,
			'DISTINCT'
		);

		if ( !$db_result ) {
			return self::newEmpty();
		}

		try {
			return self::newFromDb( $db_result );
		} catch ( MWUnitException $e ) {
			return self::newEmpty();
		}
	}

	/**
	 * Returns a new TestSuite that contains all the test that are
	 * on the page specified by the given Title object. Returns an
	 * empty TestSuite if no tests are on the given page.
	 *
	 * @param Title $title
	 * @return TestSuite
	 * @throws MWUnitException
	 */
	public static function newFromTitle( \Title $title ): TestSuite {
		return new TestSuite( [ TestClass::newFromDb( $title ) ] );
	}

	/**
	 * Returns a new TestSuite that contains the test case specified
	 * by the given test name.
	 *
	 * @param string $test_name
	 * @return TestSuite
	 * @throws MWUnitException When an invalid test name is given
	 */
	public static function newFromText( string $test_name ): TestSuite {
		if ( !strpos( $test_name, '::' ) ) {
			throw new MWUnitException( "mwunit-exception-invalid-test-name" );
		}

		list( $article_text, $test_name ) = explode( "::", $test_name );

		$title = Title::newFromText( $article_text, NS_TEST );

		if ( !$title instanceof Title || !$title->exists() ) {
			return self::newEmpty();
		}

		return new TestSuite( [ TestClass::newFromDb( $title, [ $test_name ] ) ] );
	}

	/**
	 * Creates a new TestSuite object from the given database result.
	 *
	 * @param IResultWrapper $db_result
	 * @return TestSuite
	 * @throws MWUnitException
	 */
	public static function newFromDb( IResultWrapper $db_result ) {
		$grouped_tests = [];

		// Group the database result based on article_id
		foreach ( $db_result as $item ) {
			assert( isset( $item->article_id ) );
			assert( isset( $item->test_name ) );

			$grouped_tests[$item->article_id][] = $item->test_name;
		}

		$test_classes = [];

		foreach ( $grouped_tests as $article_id => $tests ) {
			$title = \Title::newFromID( $article_id );

			if ( !$title instanceof Title || !$title->exists() ) {
				throw new MWUnitException( "mwunit-invalid-db-article-id" );
			}

			$test_classes[] = TestClass::newFromDb( $title, $tests );
		}

		return new TestSuite( $test_classes );
	}

	/**
	 * Returns a new TestSuite that contains all the test cases that cover
	 * the given template name. The template name should be given without
	 * the "Test:" namespace prefix.
	 *
	 * @param string $covers
	 * @return TestSuite
	 * @throws MWUnitException
	 */
	public static function newFromCovers( string $covers ): TestSuite {
		$dbr = wfGetDB( DB_REPLICA );

		$db_result = $dbr->select(
			'mwunit_tests',
			[ 'article_id', 'test_name' ],
			[ 'covers' => $covers ],
			__METHOD__,
			'DISTINCT'
		);

		if ( !$db_result ) {
			return self::newEmpty();
		}

		return self::newFromDb( $db_result );
	}

	/**
	 * Returns a new empty TestSuite.
	 *
	 * @return TestSuite
	 */
	public static function newEmpty(): TestSuite {
		return new TestSuite( [] );
	}

	/**
	 * TestSuite constructor.
	 *
	 * @param TestClass[] $test_classes
	 */
	public function __construct( array $test_classes ) {
		$this->test_classes = $test_classes;
	}

	/**
	 * @inheritDoc
	 * @return TestCase
	 */
	public function current() {
		return $this->test_classes[ $this->index ];
	}

	/**
	 * @inheritDoc
	 */
	public function next() {
		++$this->index;
	}

	/**
	 * @inheritDoc
	 */
	public function key() {
		return $this->index;
	}

	/**
	 * @inheritDoc
	 */
	public function valid() {
		return isset( $this->test_classes[ $this->index ] );
	}

	/**
	 * @inheritDoc
	 */
	public function rewind() {
		$this->index = 0;
	}

	/**
	 * @inheritDoc
	 */
	public function count() {
		return count( $this->test_classes );
	}
}
