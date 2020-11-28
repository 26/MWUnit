<?php

namespace MWUnit;

use MWUnit\Runner\TestRun;

/**
 * Interface TestRunInjector
 * @package MWUnit\Injector
 */
interface TestRunInjector {
	/**
	 * Dependency injector for the TestRun object.
	 *
	 * @param TestRun $run
	 */
	public static function setTestRun( TestRun $run );
}
