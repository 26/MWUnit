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
    private $flags = [];

    /**
     * @var array
     */
    private $timings = [];

    /**
     * Profiler constructor.
     */
    private function __construct() {
        $this->flag( "{start}" );
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
     * @param string $flag
     */
    public function flag( string $flag ) {
        $this->flags[]   = $flag;
        $this->timings[] = microtime( true );
    }

    /**
     * Calculates the total execution between the first and last flag.
     *
     * @return int
     */
    public function getExecutionTime() {
        $a = $this->flags[0];
        $b = $this->flags[count( $this->flags ) - 1];

        if ( $a === $b ) {
            return 0;
        }

        $a_idx = array_search( $a, $this->flags );
        $b_idx = array_search( $b, $this->flags );

        $a_time = $this->timings[$a_idx] ?? 0;
        $b_time = $this->timings[$b_idx] ?? 0;

        return $a_time > $b_time ? $a_time - $b_time : $b_time - $a_time;
    }

    /**
     * Calculates the execution time for the given flag.
     *
     * @param $flag
     * @return float
     */
    public function getFlagExecutionTime( $flag ) {
        $flag_idx = array_search( $flag, $this->flags );

        if ( $flag_idx === 0 ) {
            return 0;
        }

        $previous_idx = $flag_idx - 1;

        $flag_time     = $this->timings[$flag_idx] ?? 0;
        $previous_time = $this->timings[$previous_idx] ?? 0;

        return $flag_time - $previous_time;
    }
}