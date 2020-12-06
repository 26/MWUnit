<?php

namespace MWUnit\Tests\Integration;

use MediaWikiTestCase;
use MWUnit\Profiler;

class ProfilerTest extends MediaWikiTestCase {
	/**
	 * @var Profiler
	 */
	private $instance;

	public function setUp() : void {
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
		$this->instance->flag();
		usleep( 10 );
		$this->instance->flag();

		// getExecutionTime must now be AT LEAST 10 microseconds.

		$this->assertGreaterThan( 10, $this->instance->getTotalExecutionTime() * 1000000 );
	}

	public function testGetFlagExecutionTime() {
		for ( $i = 0; $i < 10; $i++ ) {
			$this->instance->flag();
			usleep( $i );
			$this->instance->flag();

			$this->assertGreaterThan( $i, $this->instance->getFlagExecutionTime() * 1000000 );
		}
	}
}
