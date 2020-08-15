<?php

namespace MWUnit;

use Countable;
use Iterator;
use Title;
use MWUnit\Exception\MWUnitException;

/**
 * Class TestSuite
 *
 * @package MWUnit
 */
class TestSuite implements Iterator, Countable {
    /**
     * @var array
     */
    private $test_cases;
    private $index = 0;

    /**
     * @param string $group
     * @return TestSuite
     * @throws MWUnitException
     */
    public static function newFromGroup( string $group ): TestSuite {
        $result = TestCaseRepository::getInstance()->getTestsFromGroup( $group );

        if ( !$result ) {
            return self::newEmpty();
        }

        $test_cases = [];
        foreach ( $result as $row ) {
            $test_cases[] = TestCase::newFromRow( $row );
        }

        return new TestSuite( $test_cases );
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
        $result = TestCaseRepository::getInstance()->getTestsFromTitle( $title );

        if ( !$result ) {
            return self::newEmpty();
        }

        $test_cases = [];
        foreach ( $result as $row ) {
            $test_cases[] = TestCase::newFromRow( $row );
        }

        return new TestSuite( $test_cases );
    }

    /**
     * Returns a new TestSuite that contains the test case specified
     * by the given test name.
     *
     * @param string $test_name
     * @return TestSuite
     * @throws MWUnitException When an invalid test name is given
     */
    public static function newFromText(string $test_name ): TestSuite {
        if ( !strpos( $test_name, '::' ) ) {
            throw new MWUnitException( "Invalid test name" );
        }

        $group = TestCaseRepository::getInstance()->getGroupFromTestName( $test_name );

        if ( !$group ) {
            return self::newEmpty();
        }

        if ( $group === false ) {
            throw new MWUnitException( "Invalid test name" );
        }

        list ( $page_name, $name ) = explode( "::", $test_name );
        $title = \Title::newFromText( $page_name, NS_TEST );

        return new TestSuite( [
            new TestCase( $name, $group, $title )
        ] );
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
        $result = TestCaseRepository::getInstance()->getTestsCoveringTemplate( $covers );

        if ( !$result ) {
            return self::newEmpty();
        }

        $test_cases = [];
        foreach ( $result as $row ) {
            $test_cases[] = TestCase::newFromRow( $row );
        }

        return new TestSuite( $test_cases );
    }

    /**
     * Returns a new empty TestSuite.
     *
     * @return TestSuite
     * @throws MWUnitException
     */
    public static function newEmpty(): TestSuite {
        return new TestSuite( [] );
    }

    /**
     * TestSuite constructor.
     *
     * @param array $test_cases
     * @throws MWUnitException
     */
    public function __construct( array $test_cases ) {
        foreach ( $test_cases as $test_case ) {
            if ( !$test_case instanceof TestCase ) {
                throw new MWUnitException("TestSuite must consist of only TestCase objects");
            }
        }

        $this->test_cases = $test_cases;
    }

    /**
     * @inheritDoc
     * @return ConcreteTestCase
     */
    public function current() {
        return $this->test_cases[ $this->index ];
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
        return isset( $this->test_cases[ $this->index ] );
    }

    /**
     * @inheritDoc
     */
    public function rewind() {
        $this->index = 0;
    }

    public function count() {
        return count( $this->test_cases );
    }
}