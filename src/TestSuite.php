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
     */
    public static function newFromGroup( string $group ): TestSuite {
        $result = TestCaseRepository::getInstance()->getTestsFromGroup( $group );

        if ( !$result ) {
            return self::newEmpty();
        }

        $test_cases = [];
        foreach ( $result as $row ) {
            $test_cases[] = DatabaseTestCase::newFromRow( $row );
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
     */
    public static function newFromTitle( \Title $title ): TestSuite {
        $result = TestCaseRepository::getInstance()->getTestsFromTitle( $title );

        if ( !$result ) {
            return self::newEmpty();
        }

        $test_cases = [];
        foreach ( $result as $row ) {
            $test_cases[] = DatabaseTestCase::newFromRow( $row );
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
    public static function newFromText( string $test_name ): TestSuite {
        if ( !strpos( $test_name, '::' ) ) {
            throw new MWUnitException( "mwunit-exception-invalid-test-name");
        }

        $test_case = TestCaseRepository::getInstance()->getTestCaseFromTestName( $test_name );

        if ( $test_case === false ) {
            return self::newEmpty();
        }

        return new TestSuite( [
            $test_case
        ] );
    }

    /**
     * Returns a new TestSuite that contains all the test cases that cover
     * the given template name. The template name should be given without
     * the "Test:" namespace prefix.
     *
     * @param string $covers
     * @return TestSuite
     */
    public static function newFromCovers( string $covers ): TestSuite {
        $result = TestCaseRepository::getInstance()->getTestsCoveringTemplate( $covers );

        if ( !$result ) {
            return self::newEmpty();
        }

        $test_cases = [];
        foreach ( $result as $row ) {
            $test_cases[] = DatabaseTestCase::newFromRow( $row );
        }

        return new TestSuite( $test_cases );
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
     * @param array $test_cases
     */
    public function __construct( array $test_cases ) {
        $this->test_cases = $test_cases;
    }

    /**
     * Merges the given test suite(s) with a new test suite and returns the result.
     *
     * @param TestSuite ...$test_suites
     * @return TestSuite
     */
    public function merge( TestSuite ...$test_suites ): TestSuite {
        $a = $this->test_cases;
        $b = array_map( function ( TestSuite $suite ): array {
            return $suite->getTestCases();
        }, $test_suites );

        $result = array_merge( $a, ...$b );

        return new TestSuite( $result );
    }

    public function getTestCases(): array {
        return $this->test_cases;
    }

    /**
     * @inheritDoc
     * @return TestCase
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