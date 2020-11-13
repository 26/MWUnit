<?php

namespace MWUnit\Injector;

use MWUnit\Runner\TestSuiteRunner;

interface TestSuiteRunnerInjector {
    /**
     * Dependency injector for the TestSuiteRunner object.
     *
     * @param TestSuiteRunner $runner
     */
    public static function setTestSuiteRunner( TestSuiteRunner $runner );
}