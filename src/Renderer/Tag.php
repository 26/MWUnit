<?php

namespace MWUnit\Renderer;

/**
 * Class Tag
 *
 * A very simple secure rendering engine for HTML tags.
 *
 * @package MWUnit
 */
class Tag {
	/**
	 * @var string The fully expanded tag
	 */
	private $tag;

	/**
	 * Tag constructor.
	 *
	 * @param string $element The type of element (i.e. "div", "span" or "hr")
	 * @param string|Document|Tag $content Either the content of the tag as a string (will be encoded), the content of the
	 * as a Tag object (will be expanded) or the content of the tag as a Document
	 * @param array $attributes The attributes for this tag (will be encoded)
	 * @param bool $nl2br Whether or not to convert newlines to "break" (<br/>) tags
	 */
	public function __construct( string $element, $content, array $attributes = [], bool $nl2br = false ) {
		if ( $content instanceof Document ) {
			// Expand the given Document
			$real_content = $content->__toString();
		} elseif ( $content instanceof Tag ) {
			// Expand the given tag
			$real_content = $content->__toString();
		} elseif ( is_string( $content ) ) {
			// Encode all free text
			$real_content = htmlspecialchars( $content, ENT_QUOTES );
		} else {
			// The given content has an invalid type
			throw new \InvalidArgumentException( '$content must either be a Document, a Tag or a string' );
		}

		$real_attributes = [];
		foreach ( $attributes as $key => $value ) {
			if ( !is_string( $key ) || !is_string( $value ) ) {
				// The attribute is invalid
				throw new \InvalidArgumentException( '$attributes must only consist of string key-value pairs' );
			}

			// Encode the attribute and append it to the collector
			$real_attributes[ htmlspecialchars( $key, ENT_QUOTES ) ] = htmlspecialchars( $value, ENT_QUOTES );
		}

		if ( $nl2br ) {
			$real_content = nl2br( $real_content );
		}

		// Create the tag; we create the tag in the constructor, because it will never change and it would be wasteful
		// to recalculate the tag each time it is requested.
		$this->tag = \Xml::tags( htmlspecialchars( $element, ENT_QUOTES ), $real_attributes, $real_content );
	}

	/**
	 * Returns the tag as a string.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->tag;
	}
}
