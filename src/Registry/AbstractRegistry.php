<?php

namespace MWUnit\Registry;

abstract class AbstractRegistry {
    /**
     * AbstractRegistry constructor.
     */
    protected function __construct() {}

    /**
     * Gets an instance of the current class.
     *
     * @return AbstractRegistry
     */
    abstract public static function getInstance(): AbstractRegistry;

    /**
     * Sets the instance of the current class if and only if it has
     * not yet been set.
     *
     * @return void
     */
    abstract protected static function setInstance();
}