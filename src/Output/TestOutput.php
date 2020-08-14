<?php

namespace MWUnit\Output;

/**
 * Interface TestOutput
 *
 * @package MWUnit\Debug
 */
interface TestOutput {
    /**
     * TestOutput constructor.
     *
     * @param mixed $output The value the test put out.
     */
    public function __construct( $output );

    /**
     * Returns the value the test put out. This value may be a class, string or other value.
     *
     * @return mixed
     */
    public function getOutput();

    /**
     * Returns the value the test put out as a string.
     *
     * @return string
     */
    public function __toString(): string;
}