<?php

namespace MWUnit;

use MWUnit\Runner\Result\TestResult;
use MWUnit\Runner\TestRun;

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
	 * @var TestCase[]
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
	 * @var int
	 */
	private $skipped_count = 0;

	/**
	 * TestRunStore constructor.
	 *
	 * @param array $runs
	 */
	public function __construct( array $runs = [] ) {
		foreach ( $runs as $run ) {
			$this->append( $run );
		}
	}

	/**
	 * @param TestRun $run
	 */
	public function append( TestRun $run ) {
		$this->runs[] = $run;

		switch ( $run->getResult()->getResultConstant() ) {
			case TestResult::T_SUCCESS:
				$this->success_count++;
				break;
			case TestResult::T_RISKY:
				$this->risky_count++;
				break;
			case TestResult::T_FAILED:
				$this->failed_count++;
				break;
			case TestResult::T_SKIPPED:
				$this->skipped_count++;
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
	 */
	public function getFailedRuns(): TestRunStore {
		return $this->getStoreWithResult( TestResult::T_FAILED );
	}

	/**
	 * Returns the risky TestRun objects in the store as a TestRunStore object.
	 *
	 * @return TestRunStore
	 */
	public function getRiskyRuns(): TestRunStore {
		return $this->getStoreWithResult( TestResult::T_RISKY );
	}

	/**
	 * Returns the TestRun objects in this store that have a given result, as a TestStoreObject.
	 *
	 * @param int $result Either T_RISKY, T_FAILED or T_SUCCESS
	 * @return TestRunStore
	 */
	public function getStoreWithResult( int $result ): TestRunStore {
		return new TestRunStore( array_filter( $this->runs, function ( TestRun $run ) use ( $result ): bool {
			return $run->getResult()->getResultConstant() === $result;
		} ) );
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

	/**
	 * Returns the number of tests skipped.
	 *
	 * @return int
	 */
	public function getSkippedCount(): int {
		return $this->skipped_count;
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
