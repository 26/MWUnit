<?php

namespace MWUnit\Injector;

use MWUnit\Runner\TestRun;

/**
 * Interface TestRunInjector
 * @package MWUnit\Injector
 */
interface TestRunInjector extends InjectorInterface {
    /**
     * Dependency injector for the TestRun object.
     *
     * @param TestRun $run
     */
    public static function setTestRun( TestRun $run );
}