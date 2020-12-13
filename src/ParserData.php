<?php

namespace MWUnit;

use Iterator;
use OutOfBoundsException;
use Parser;
use PPFrame;
use PPNode;

/**
 * Class ParserData
 *
 * @package MWUnit
 */
class ParserData implements Iterator {
	/**
	 * @var PPNode[]
	 */
	private $data;

	/**
	 * @var TestPageParser
	 */
	private $parser;

	/**
	 * @var PPFrame
	 */
	private $frame;

	/**
	 * @var int
	 */
	private $index;

	/**
	 * @var int
	 */
	private $flags;

	/**
	 * @var int
	 */
	private $count;

	/**
	 * @var string
	 */
	private $input = '';

	/**
	 * ParserData constructor.
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $data
	 */
	public function __construct( Parser $parser, PPFrame $frame, array $data ) {
		$this->parser   = $parser;
		$this->frame    = $frame;
		$this->data     = $data;
		$this->flags    = 0;
	}

	/**
	 * Set optional input given to the parser hook.
	 *
	 * @param string $input
	 */
	public function setInput( string $input ) {
		$this->input = $input;
	}

	/**
	 * Sets the flags to use for the expansion of the PPNode.
	 *
	 * @param int $flags
	 */
	public function setFlags( int $flags ) {
		$this->flags = $flags;
	}

	/**
	 * Returns the input given to this parser hook.
	 *
	 * @return string
	 */
	public function getInput(): string {
		return $this->input;
	}

	/**
	 * Returns the flags currently set to use for expansion.
	 *
	 * @return int
	 */
	public function getFlags(): int {
		return $this->flags;
	}

	/**
	 * Gets the Parser object.
	 *
	 * @return Parser
	 */
	public function getParser(): Parser {
		return $this->parser;
	}

	/**
	 * Gets the Frame object.
	 *
	 * @return PPFrame
	 */
	public function getFrame(): PPFrame {
		return $this->frame;
	}

	/**
	 * Returns the expanded arguments to the parser function.
	 *
	 * @return string[]
	 */
	public function getArguments(): array {
		return array_map( function ( $node ): string {
			return $this->expand( $node );
		}, $this->data );
	}

	/**
	 * Returns the given argument.
	 *
	 * @param int $index
	 * @return string
	 * @throws OutOfBoundsException
	 */
	public function getArgument( int $index ): string {
		if ( !isset( $this->data[$index] ) ) {
			throw new OutOfBoundsException();
		}

		return $this->expand( $this->data[$index] );
	}

	/**
	 * Returns the number of arguments in the parser function call.
	 *
	 * @return int
	 */
	public function count(): int {
		if ( !isset( $this->count ) ) {
			$this->count = count( $this->data );
		}

		return $this->count;
	}

	/**
	 * Returns the expanded version of the data.
	 *
	 * @return string
	 */
	public function current(): string {
		return $this->expand( $this->data[ $this->index ] );
	}

	/**
	 * @inheritDoc
	 */
	public function next() {
		++$this->index;
	}

	/**
	 * @inheritDoc
	 */
	public function key() {
		return $this->index;
	}

	/**
	 * @inheritDoc
	 */
	public function valid() {
		return isset( $this->data[ $this->index ] );
	}

	/**
	 * @inheritDoc
	 */
	public function rewind() {
		$this->index = 0;
	}

	/**
	 * Returns the expanded version of the given value.
	 *
	 * @param string|PPNode $value
	 * @return string
	 */
	private function expand( $value ): string {
		return trim( $this->frame->expand( $value, $this->flags ) );
	}
}
