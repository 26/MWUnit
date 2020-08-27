<?php

namespace MWUnit\Tests\Integration;

use MediaWikiTestCase;
use MWUnit\Profiler;

class ProfilerTest extends MediaWikiTestCase {
    /**
     * @var Profiler
     */
    private $instance;

    public function setUp() {
        parent::setUp();

        $this->instance = Profiler::getInstance();
    }

    public function testGetInstance() {
        $this->assertInstanceOf( Profiler::class, $this->instance );
    }

    public function testPeakMemoryIsReasonable() {
        $memory_use = $this->instance->getPeakMemoryUse();
        $this->assertLessThan( 1024 * 1024 * 1024, $memory_use );
    }

    public function testPeakMemoryIsGreaterThanZero() {
        $memory_use = $this->instance->getPeakMemoryUse();
        $this->assertGreaterThan( 0, $memory_use );
    }

    public function testGetExecutionTime() {
        usleep( 10 );
        $this->instance->flag( '{end}' );

        // getExecutionTime must now be AT LEAST 10 microseconds.

        $this->assertGreaterThan( 10, $this->instance->getExecutionTime() * 1000000 );
    }

    public function testGetFlagExecutionTime() {
        for ( $i = 0; $i < 10; $i++ ) {
            $this->instance->flag( '{start}' );
            usleep( $i );
            $this->instance->flag( '{end}' );

            $this->assertGreaterThan( $i, $this->instance->getFlagExecutionTime( '{end}' ) * 1000000 );
        }
    }
}