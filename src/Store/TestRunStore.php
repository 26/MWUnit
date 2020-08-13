<?php

namespace MWUnit\Store;

use MWUnit\Exception\MWUnitException;
use MWUnit\Runner\Result\TestResult;
use MWUnit\Runner\TestRun;
use MWUnit\TestCase;

class TestRunStore implements \Iterator {
    /**
     * @var array
     */
    private $runs;

    private $index;

    /**
     * TestRunStore constructor.
     *
     * @param array $runs
     * @throws MWUnitException
     */
    public function __construct( array $runs = [] ) {
        foreach ( $runs as $run ) {
            if ( !$run instanceof TestRun ) {
                throw new MWUnitException( "TestRunStore can only contain TestRun objects" );
            }
        }

        $this->runs = $runs;
        $this->index = 0;
    }

    public function append( TestRun $run ) {
        $this->runs[] = $run;
    }

    /**
     * Returns all the TestRun objects in the store.
     *
     * @return array
     */
    public function getRuns(): array {
        return $this->runs;
    }

    /**
     * Returns the failed TestRun objects in the store as a TestRunStore object.
     *
     * @return TestRunStore
     */
    public function getFailedRuns(): TestRunStore {
        return $this->getRunsWithResult( TestResult::T_FAILED );
    }

    /**
     * Returns the risky TestRun objects in the store as a TestRunStore object.
     *
     * @return TestRunStore
     */
    public function getRiskyRuns(): TestRunStore {
        return $this->getRunsWithResult( TestResult::T_RISKY );
    }

    /**
     * Returns the TestRun objects in this store that have a given result, as a TestStoreObject.
     *
     * @param int $result Either T_RISKY, T_FAILED or T_SUCCESS
     * @return TestRunStore
     */
    public function getRunsWithResult( int $result ): TestRunStore {
        try {
            return new TestRunStore( array_filter( $this->runs, function( TestRun $run ) use ( $result ): bool {
                return $run->getResult()->getResult() === $result;
            } ) );
        } catch( MWUnitException $e ) {
            return new TestRunStore;
        }
    }

    /**
     * Returns all the TestResult objects contained in the TestRun objects.
     *
     * @return array
     */
    public function getTestResults(): array {
        return array_map( function ( TestRun $run ): TestResult {
            return $run->getResult();
        }, $this->runs );
    }

    /**
     * Returns all the TestCase objects contained in the TestRun objects.
     *
     * @return array
     */
    public function getTestCases(): array {
        return array_map( function ( TestRun $run ): TestCase {
            return $run->getTestCase();
        }, $this->runs );
    }

    /**
     * @inheritDoc
     */
    public function current(): TestRun {
        return $this->runs[$this->index];
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
    public function key(): int {
        return $this->index;
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool {
        return isset( $this->runs[$this->index ] );
    }

    /**
     * @inheritDoc
     */
    public function rewind() {
        $this->index = 0;
    }

    /**
     * @return int
     */
    public function count() {
        return count( $this->runs );
    }
}