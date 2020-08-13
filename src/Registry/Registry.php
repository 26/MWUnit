<?php

namespace MWUnit\Registry;

interface Registry {
    /**
     * Gets an instance of the current class.
     *
     * @return Registry
     */
    static function getInstance(): Registry;

    /**
     * Sets the instance of the current class if and only if it has
     * not yet been set.
     *
     * @return void
     */
    static function setInstance();

    // TODO: Make this interface more tailored towards a register, with get and register functions
}