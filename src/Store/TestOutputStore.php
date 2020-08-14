<?php

namespace MWUnit\Store;

use MWUnit\Output\TestOutput;
use MWUnit\Exception\MWUnitException;

class TestOutputStore implements StoreInterface {
    private $index;
    private $outputs;

    /**
     * TestOutputCollector constructor.
     */
    public function __construct() {
        $this->index = 0;
        $this->outputs = [];
    }

    /**
     * Append a new TestOutput to the collector.
     *
     * @param TestOutput $output
     * @throws MWUnitException
     */
    public function append( $output ) {
        if ( !$output instanceof TestOutput ) {
            throw new MWUnitException( "TestOutputStore can only contain TestOutput objects" );
        }

        $this->outputs[] = $output;
    }

    /**
     * Returns the outputs already collected by the collector.
     *
     * @return array
     */
    public function getAll(): array {
        return $this->outputs;
    }

    /**
     * @inheritDoc
     * @return TestOutput
     */
    public function current(): TestOutput {
        return $this->outputs[$this->index];
    }

    /**
     * @inheritDoc
     */
    public function next() {
        ++$this->index;
    }

    /**
     * @inheritDoc
     * @return int
     */
    public function key(): int {
        return $this->index;
    }

    /**
     * @inheritDoc
     * @return bool
     */
    public function valid(): bool {
        return isset( $this->outputs[$this->index] );
    }

    /**
     * @inheritDoc
     */
    public function rewind() {
        $this->index = 0;
    }
}