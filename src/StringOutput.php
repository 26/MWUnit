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

    public function __construct( string $output ) {
        $this->output = $output;
    }

    public function getOutput(): string {
        return $this->output;
    }

    public function __toString(): string {
        return $this->output;
    }
}