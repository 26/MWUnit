<?php

namespace MWUnit;

use DOMDocument;

class TestPageParser {
	/**
	 * @var array
	 */
	const TAGS = [
		"setup",
		"teardown",
		"testcase",
	]; // phpcs:ignore

	/**
	 * @var array Key-value pairs of strip markers with their stripped content.
	 */
	private $strip_markers = [];

	/**
	 * Parses the given wikitext and returns an associative array. The key
	 * of the array is the type of the tag (i.e. "setup", "teardown", etc...)
	 * and the values are an array of DOMNode objects of that type.
	 *
	 * @param string $wikitext
	 * @return array[]
	 */
	public function parse( string $wikitext ): array {
		// Replace all relevant HTML tags with strip markers
		$wikitext = $this->setStripMarkers( $wikitext );

		// Escape remaining wikitext
		$wikitext = htmlspecialchars( $wikitext );

		// Replace all strip markers with corresponding HTML tags
		$wikitext = $this->removeStripMarkers( $wikitext );

		// Parse the document using DOMDocument
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		@$dom->loadHTML( $wikitext ); // phpcs:ignore

		$tags = [];

		foreach ( self::TAGS as $tag ) {
			$tags[$tag] = $dom->getElementsByTagName( $tag );
		}

		return $tags;
	}

	/**
	 * Sets the strip markers for the given wikitext.
	 *
	 * @param string $wikitext
	 * @return string
	 */
	private function setStripMarkers( string $wikitext ): string {
		foreach ( self::TAGS as $tag ) {
			$wikitext = $this->setTagStripMarkers( $tag, $wikitext );
		}

		return $wikitext;
	}

	/**
	 * Sets the strip markers for the given "tag".
	 *
	 * @param string $tag
	 * @param string $wikitext
	 * @return string
	 */
	private function setTagStripMarkers( string $tag, string $wikitext ): string {
		$opening_tag_regex = "/<" . preg_quote( $tag ) . ".+?>/s";
		$closing_tag_regex = "/<\/" . preg_quote( $tag ) . ">/";

		// Match opening tag
		$wikitext = preg_replace_callback(
			$opening_tag_regex,
			[ $this, "setStripMarker" ],
			$wikitext
		);

		// Match closing tag
		$wikitext = preg_replace_callback(
			$closing_tag_regex,
			[ $this, "setStripMarker" ],
			$wikitext
		);

		return $wikitext;
	}

	/**
	 * Set a new strip marker and adds it to the list of strip markers. Used
	 * as a callback for preg_replace_callback.
	 *
	 * @param array $matches
	 * @return string
	 */
	private function setStripMarker( array $matches ): string {
		$strip_marker = "`UNIQ`-" . md5( rand() ) . "-`QINU`";
		$this->strip_markers[$strip_marker] = $matches[0];
		return $strip_marker;
	}

	/**
	 * Removes the known strip markers from the given wikitext.
	 *
	 * @param string $wikitext
	 * @return string
	 */
	private function removeStripMarkers( string $wikitext ) {
		return strtr( $wikitext, $this->strip_markers );
	}
}
