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
     * @param WikiPage $wikiPage
     * @param Content $content
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

        global $wgVersion;
        $context = version_compare( $wgVersion, '1.32', '<' ) ? null : 'canonical';

        $text  = ContentHandler::getContentText( $content );
        $title = $wikiPage->getTitle();

        $options = $wikiPage->makeParserOptions( $context );

        $parser = MediaWikiServices::getInstance()->getParser();

        if ( $use_fresh_parser ) {
            $parser = $parser->getFreshParser();
        }

        $parser->parse( $text, $title, $options );
    }
}