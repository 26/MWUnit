<?php

namespace MWUnit\Store;

/**
 * Interface StoreInterface
 *
 * @package MWUnit\Store
 */
interface StoreInterface extends \Iterator {
    /**
     * Appends a value to the store.
     *
     * @param mixed $value
     * @return void
     */
    public function append( $value );

    /**
     * Returns an array of all items in the store.
     *
     * @return array
     */
    public function getAll(): array;
}