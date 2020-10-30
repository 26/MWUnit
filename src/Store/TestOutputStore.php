<?php

namespace MWUnit\Store;

class TestOutputStore implements \Iterator, \Countable {
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
     * @param string $output
     */
    public function append( string $output) {
        $this->outputs[] = $output;
    }

    /**
     * Returns the outputs already collected by the collector.
     *
     * @return string[]
     */
    public function getAll(): array {
        return $this->outputs;
    }

    public function current(): string {
        return $this->outputs[$this->index];
    }

    public function next() {
        ++$this->index;
    }

    public function key(): int {
        return $this->index;
    }

    public function valid(): bool {
        return isset( $this->outputs[$this->index] );
    }

    public function rewind() {
        $this->index = 0;
    }

    public function count() {
        return count( $this->outputs );
    }
}