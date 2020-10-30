<?php

namespace MWUnit;

use Content;
use ContentHandler;
use DOMDocument;
use DOMNode;
use MediaWiki\MediaWikiServices;
use ParserOptions;
use WikiPage;

/**
 * Class WikitextParser
 *
 * @package MWUnit
 */
class WikitextParser {
    /**
     * Returns an array of all test cases in the given wikitext. The output has the following format:
     *
     * [
     *  [
     *      "content": "<tagcontent>",
     *      "attributes": [
     *          "<attribute>" => "<value>"
     *      ]
     *  ]
     * ]
     *
     * @param string $text
     * @return array[]
     */
    public static function getTestCasesFromWikitext( string $text ): array {
        $dom = new DOMDocument();

        // We use "@" to suppress the warning about invalid HTML tags.
        @$dom->loadHTML( $text );

        // Filter all empty tags
        $valid_tags = array_filter( iterator_to_array( $dom->getElementsByTagName( "testcase" ) ), function( DOMNode $tag ) {
            return $tag->textContent !== "";
        } );

        return array_map( function( DOMNode $tag ): array {
            $content    = trim( $tag->textContent );
            $attributes = self::getAttributesFromTag( $tag );

            return [
                "content" => $content,
                "attributes" => $attributes
            ];
        }, $valid_tags );
    }

    /**
     * Returns a list of key-value pairs, where the key is the name of the attribute
     * and the value is the content of the attribute.
     *
     * @param DOMNode $dom_node
     * @return array
     */
    public static function getAttributesFromTag( DOMNode $dom_node ): array {
        if ( !$dom_node->hasAttributes() ) {
            return [];
        }

        $attributes     = [];
        $dom_attributes = $dom_node->attributes;

        foreach ( $dom_attributes as $attribute ) {
            $name       = $attribute->name;
            $content    = $attribute->textContent;

            $attributes[$name] = $content;
        }

        return $attributes;
    }
}