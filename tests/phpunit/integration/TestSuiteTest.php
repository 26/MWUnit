<?php

namespace MWUnit\Tests\Integration;

use MediaWikiTestCase;
use MWUnit\TestSuite;

/**
 * Class TestSuiteTest
 */
class TestSuiteTest extends MediaWikiTestCase {
    public function testNewEmptyIsEmpty() {
        $empty_suite = TestSuite::newEmpty();
        $this->assertSameSize( $empty_suite, [] );
    }

    public function testSizeEqualsInputArray() {
        for ( $i = 0; $i < 1000; $i++ ) {
            $input = array_fill( 0, $i, "a" );
            $this->assertSameSize( $input, new TestSuite( $input ) );
        }
    }

    public function testMerge() {
        for ( $i = 0; $i < 100; $i++ ) {
            $a = array_fill( 0, $i, "a" );
            $b = array_fill( 0, $i, "b" );

            $merge_array = array_merge( $a, $b );
            $merge_suites = ( new TestSuite( $a ) )->merge( new TestSuite( $b ) );

            $this->assertArrayEquals( $merge_array, iterator_to_array( $merge_suites ) );
        }
    }

    public function testCasesAreUnmodifiedWhenGet() {
        for ( $i = 0; $i < 1000; $i++ ) {
            $value = md5( rand() );
            $array = array_fill( 0, $i, $value );
            $suite = new TestSuite( $array );

            foreach ( $suite->getTestCases() as $case ) {
                $this->assertSame( $value, $case );
            }
        }
    }

    public function testIterator() {
        for ( $i = 0; $i < 1000; $i++ ) {
            $value = md5( rand() );
            $array = array_fill( 0, $i, $value );
            $suite = new TestSuite( $array );

            foreach ( $suite as $case ) {
                $this->assertSame( $value, $case );
            }
        }
    }

    public function testCount() {
        for ( $i = 0; $i < 1000; $i++ ) {
            $value = md5( rand() );
            $array = array_fill( 0, $i, $value );
            $suite = new TestSuite( $array );

            $this->assertSame( $i, count( $suite ) );
        }
    }
}