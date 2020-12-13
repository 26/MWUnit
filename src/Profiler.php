<?php

namespace MWUnit;

/**
 * Class Profiler
 *
 * Simple class for profiling test runs.
 *
 * @package MWUnit
 */
class Profiler {
	/**
	 * @var Profiler
	 */
	private static $instance;

	/**
	 * @var array
	 */
	private $timings = [];

	/**
	 * Profiler constructor.
	 */
	private function __construct() {
		$this->flag();
	}

	/**
	 * Resets the Profiler singleton.
	 */
	public static function reset() {
		self::$instance = new self();
	}

	/**
	 * Gets the Profiler instance.
	 *
	 * @return Profiler
	 */
	public static function getInstance() {
		if ( !isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Gets the peak memory usage for the current script.
	 *
	 * @return string
	 */
	public function getPeakMemoryUse(): string {
		return memory_get_peak_usage();
	}

	/**
	 * Adds a new flag.
	 */
	public function flag() {
		$this->timings[] = microtime( true );
	}

	/**
	 * Calculates the total execution between the first and last flag.
	 *
	 * @return float
	 */
	public function getTotalExecutionTime() {
		if ( count( $this->timings ) === 0 ) {
			return 0;
		}

		return $this->timings[count( $this->timings ) - 1] - $this->timings[0];
	}

	/**
	 * Calculates the execution time between the current flag and the previous flag.
	 *
	 * @return float
	 */
	public function getFlagExecutionTime() {
		$timings_count = count( $this->timings );

		if ( $timings_count < 2 ) {
			return 0;
		}

		$idx_current = $timings_count - 1;
		$idx_previous = $idx_current - 1;

		$current_time = $this->timings[$idx_current] ?? 0;
		$previous_time = $this->timings[$idx_previous] ?? 0;

		return $current_time - $previous_time;
	}
}
