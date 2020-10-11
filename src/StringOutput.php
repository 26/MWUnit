<?php

namespace MWUnit;

use MWUnit\Exception\MWUnitException;

/**
 * Class StringOutput
 *
 * @package MWUnit
 */
class StringOutput {
    /**
     * @var string
     */
    public $output;

    /**
     * @param $output
     * @throws MWUnitException When the given input is not a string
     */
    public function __construct( $output ) {
        if ( !is_string( $output ) ) {
            throw new MWUnitException(
                "`output` must be of type string, " . gettype( $output ) . " given."
            );
        }

        $this->output = $output;
    }

    public function getOutput(): string {
        return $this->output;
    }

    public function __toString(): string {
        return $this->output;
    }
}