<?php

namespace MWUnit\Renderer;

/**
 * Class Document
 *
 * A document consists of zero or more adjacent tags.
 *
 * @package MWUnit\Renderer
 */
class Document {
	/**
	 * @var string The expanded document
	 */
	private $document;

	/**
	 * Document constructor.
	 *
	 * @param array $tags
	 */
	public function __construct( array $tags ) {
		$tags = array_filter( $tags, function ( $a ): bool {
			return $a !== null;
		} );

		// "array_reduce" takes an initial $carry (""), a closure and an array an reduces that array to a single value.
		// It does this by calling the closure for each element in the array, passing the return value of the previous call
		// as $carry and passing the current array item as $item.
		$this->document = array_reduce( $tags, function ( string $carry, Tag $item ): string {
			return $carry . $item->__toString();
		}, "" );
	}

	/**
	 * Returns this document as a string.
	 */
	public function __toString(): string {
		return $this->document;
	}
}
