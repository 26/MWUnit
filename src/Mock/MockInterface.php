<?php

namespace MWUnit\Mock;

/**
 * Interface Mock
 *
 * @package MWUnit\Mock
 */
interface MockInterface {
    /**
     * Sets the content of the mock.
     *
     * @param string $mock_content
     * @return void
     */
    public function setMock( string $mock_content );

    /**
     * Returns the content of the mock.
     *
     * @return string
     */
    public function getMock(): string;
}