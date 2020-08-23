<?php

namespace MWUnit\Output;

use MWUnit\Exception\MWUnitException;

/**
 * Class StringOutput
 *
 * @package MWUnit\Output
 */
class StringOutput implements TestOutput {
    /**
     * @var string
     */
    public $output;

    /**
     * @inheritDoc
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

    /**
     * @inheritDoc
     */
    public function getOutput(): string {
        return $this->output;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string {
        return $this->output;
    }
}