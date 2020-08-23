<?php

namespace MWUnit\Injector;

use MWUnit\Runner\TestSuiteRunner;

interface TestSuiteRunnerInjector extends InjectorInterface {
    /**
     * Dependency injector for the TestSuiteRunner object.
     *
     * @param TestSuiteRunner $runner
     */
    public static function setTestSuiteRunner( TestSuiteRunner $runner );
}