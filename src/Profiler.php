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
     * @return float
     */
    public function getTotalExecutionTime() {
        assert( count( $this->timings ) > 0 );

        return $this->timings[count($this->timings) - 1] - $this->timings[0];
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