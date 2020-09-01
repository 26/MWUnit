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

        return array_map( function( DOMNode $tag ): array {
            $content    = trim( $tag->textContent );
            $attributes = self::getAttributesFromTag( $tag );

            return [
                "content" => $content,
                "attributes" => $attributes
            ];
        }, array_filter( iterator_to_array( $dom->getElementsByTagName("testcase") ), function( DOMNode $tag ) {
            return $tag->textContent !== "";
        } ) );
    }

    /**
     * Parses the given content in the given page's context.
     *
     * @param WikiPage $wikiPage The WikiPage object to use for context
     * @param Content $content The Content object to parse
     * @param bool $use_fresh_parser Whether or not to use a fresh parser, or the parser from MediaWikiServices
     */
    public static function parseContentFromWikiPage(
        WikiPage $wikiPage,
        Content $content,
        bool $use_fresh_parser = false
    ) {
        MWUnit::getLogger()->debug( '(Re)parsing wikitext for article {id}', [
            'id' => $wikiPage->getTitle()->getFullText()
        ] );

        $text    = $content->getNativeData();
        $title   = $wikiPage->getTitle();
        $options = $wikiPage->makeParserOptions( 'canonical' );
        $parser  = MediaWikiServices::getInstance()->getParser();

        if ( $use_fresh_parser ) {
            $parser = $parser->getFreshParser();
        }

        $parser->parse( $text, $title, $options );
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