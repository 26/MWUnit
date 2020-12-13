<?php

namespace MWUnit\Tests\Integration;

use MediaWikiTestCase;
use MWUnit\Profiler;

class ProfilerTest extends MediaWikiTestCase {
	/**
	 * @var Profiler
	 */
	private $instance;

	/**
	 * Set up the Profiler for the test case.
	 */
	public function setUp() : void {
		parent::setUp();
		$this->instance = Profiler::getInstance();
	}

	/**
	 * Reset the Profiler after each test case.
	 */
	public function tearDown() : void {
		parent::tearDown();
		Profiler::reset();
	}

	/**
	 * Sets if "getInstance" returns an instance of Profiler.
	 *
	 * @covers \MWUnit\Profiler::getInstance
	 */
	public function testGetInstance() {
		$this->assertInstanceOf( Profiler::class, $this->instance );
	}

	/**
	 * Checks whether the peak memory value returned by the profiler is
	 * reasonable.
	 *
	 * @covers \MWUnit\Profiler::getPeakMemoryUse
	 */
	public function testPeakMemoryIsReasonable() {
		$memory_use = $this->instance->getPeakMemoryUse();
		$this->assertLessThan( 1024 * 1024 * 1024, $memory_use );
	}

	/**
	 * Checks whether the peak memory value returned by the profiler
	 * is greater than zero.
	 *
	 * @covers \MWUnit\Profiler::getPeakMemoryUse
	 */
	public function testPeakMemoryIsGreaterThanZero() {
		$memory_use = $this->instance->getPeakMemoryUse();
		$this->assertGreaterThan( 0, $memory_use );
	}

	/**
	 * @covers \MWUnit\Profiler::getTotalExecutionTime
	 */
	public function testGetExecutionTime() {
		$this->instance->flag();
		usleep( 10 );
		$this->instance->flag();

		// getExecutionTime must now be AT LEAST 10 microseconds.

		$this->assertGreaterThan( 10, $this->instance->getTotalExecutionTime() * 1000000 );
	}

	/**
	 * @covers \MWUnit\Profiler::getFlagExecutionTime
	 */
	public function testGetFlagExecutionTime() {
		for ( $i = 0; $i < 10; $i++ ) {
			$this->instance->flag();
			usleep( $i );
			$this->instance->flag();

			$this->assertGreaterThan( $i, $this->instance->getFlagExecutionTime() * 1000000 );
		}
	}
}
