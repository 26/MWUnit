<?php

namespace MWUnit\Debug;

class TestOutputCollector implements \Iterator {
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
     */
    public function append( TestOutput $output ) {
        $this->outputs[] = $output;
    }

    /**
     * Returns the outputs already collected by the collector.
     *
     * @return array
     */
    public function getOutputs(): array {
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