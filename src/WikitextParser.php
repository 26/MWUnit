<?php

namespace MWUnit;

use Content;
use ContentHandler;
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
     * Parses the given content in the given page's context.
     *
     * @param WikiPage $wikiPage The WikiPage object to use for context
     * @param Content $content The Content object to parse
     * @param bool $use_fresh_parser Whether or not to use a fresh parser, or the parser from MediaWikiServices
     * @throws \MWException
     */
    public static function parseContentFromWikiPage(
        WikiPage $wikiPage,
        Content $content,
        bool $use_fresh_parser = false
    ) {
        MWUnit::getLogger()->debug( '(Re)parsing wikitext for article {id}', [
            'id' => $wikiPage->getTitle()->getFullText()
        ] );

        // In the future, $wgVersion will be replaced with the MW_VERSION constant. For backwards
        // compatibility reasons, $wgVersion will still be used here.

        $text    = ContentHandler::getContentText( $content );
        $title   = $wikiPage->getTitle();
        $options = $wikiPage->makeParserOptions( 'canonical' );
        $parser  = MediaWikiServices::getInstance()->getParser();

        if ( $use_fresh_parser ) {
            $parser = $parser->getFreshParser();
        }

        $parser->parse( $text, $title, $options );
    }
}