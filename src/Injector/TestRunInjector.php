<?php

namespace MWUnit\Injector;

use MWUnit\Runner\TestRun;

interface TestRunInjector {
    /**
     * Dependency injector for the TestRun object.
     *
     * @param TestRun $run
     */
    public static function setTestRun( TestRun $run );
}