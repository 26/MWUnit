<?php

namespace MWUnit\Store;

use MWUnit\Exception\MWUnitException;
use MWUnit\Runner\Result\TestResult;
use MWUnit\Runner\TestRun;
use MWUnit\DatabaseTestCase;

class TestRunStore implements \Iterator, \Countable {
    /**
     * @var array
     */
    private $runs = [];

    /**
     * @var int
     */
    private $index = 0;

    /**
     * @var TestResult[]
     */
    private $test_results = [];

    /**
     * @var DatabaseTestCase[]
     */
    private $test_cases = [];

    /**
     * @var int
     */
    private $success_count = 0;

    /**
     * @var int
     */
    private $risky_count = 0;

    /**
     * @var int
     */
    private $failed_count = 0;

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

        $this->index = 0;

        foreach( $runs as $run ) {
            $this->append( $run );
        }
    }

    /**
     * @param TestRun $run
     */
    public function append( TestRun $run ) {
        $this->runs[] = $run;

        $result = $run->getResult();
        $this->test_results[] = $result;

        switch ( $result->getResultConstant() ) {
            case TestResult::T_SUCCESS:
                $this->success_count++;
                break;
            case TestResult::T_RISKY:
                $this->risky_count++;
                break;
            case TestResult::T_FAILED:
                $this->failed_count++;
                break;
        }

        $this->test_cases[] = $run->getTestCase();
    }

    /**
     * @return TestRun[]
     */
    public function getAll(): array {
        return $this->runs;
    }

    /**
     * Returns the failed TestRun objects in the store as a TestRunStore object.
     *
     * @return TestRunStore
     * @throws MWUnitException
     */
    public function getFailedRuns(): TestRunStore {
        return $this->getRunsWithResult( TestResult::T_FAILED );
    }

    /**
     * Returns the risky TestRun objects in the store as a TestRunStore object.
     *
     * @return TestRunStore
     * @throws MWUnitException
     */
    public function getRiskyRuns(): TestRunStore {
        return $this->getRunsWithResult( TestResult::T_RISKY );
    }

    /**
     * Returns the TestRun objects in this store that have a given result, as a TestStoreObject.
     *
     * @param int $result Either T_RISKY, T_FAILED or T_SUCCESS
     * @return TestRunStore
     * @throws MWUnitException
     */
    public function getRunsWithResult( int $result ): TestRunStore {
        return new TestRunStore( array_filter( $this->runs, function( TestRun $run ) use ( $result ): bool {
            return $run->getResult()->getResultConstant() === $result;
        } ) );
    }

    /**
     * Returns all the TestResult objects contained in the TestRun objects.
     *
     * @return array
     */
    public function getTestResults(): array {
        return $this->test_results;
    }

    /**
     * Returns all the DatabaseTestCase objects contained in the TestRun objects.
     *
     * @return array
     */
    public function getTestCases(): array {
        return $this->test_cases;
    }

    /**
     * Returns the number of tests that passed.
     *
     * @return int
     */
    public function getSuccessCount(): int {
        return $this->success_count;
    }

    /**
     * Returns the number of tests that were marked as risky.
     *
     * @return int
     */
    public function getRiskyCount(): int {
        return $this->risky_count;
    }

    /**
     * Returns the number of tests that failed.
     *
     * @return int
     */
    public function getFailedCount(): int {
        return $this->failed_count;
    }

    public function current(): TestRun {
        return $this->runs[$this->index];
    }

    public function next() {
        ++$this->index;
    }

    public function key(): int {
        return $this->index;
    }

    public function valid(): bool {
        return isset( $this->runs[$this->index] );
    }

    public function rewind() {
        $this->index = 0;
    }

    public function count() {
        return count( $this->runs );
    }
}