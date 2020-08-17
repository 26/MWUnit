<?php

namespace MWUnit\Registry;

/**
 * Interface Registry
 *
 * @package MWUnit\Registry
 */
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

    /**
     * Gets the value specified by $key.
     *
     * @param mixed $key
     * @return mixed
     */
    function get( $key );

    /**
     * Registers $value to the register with the identifier $key.
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    function register( $key, $value );

    /**
     * Returns true if and only if $key is registered.
     *
     * @param $key
     * @return bool
     */
    function exists( $key ): bool;

    /**
     * Resets the register.
     *
     * @return void
     */
    function reset();
}