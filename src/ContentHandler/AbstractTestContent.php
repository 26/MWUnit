<?php

namespace MWUnit\ContentHandler;

use DifferenceEngine;
use DOMDocument;
use DOMNode;
use MediaWiki\MediaWikiServices;
use MWUnit\MWUnit;
use MWUnit\WikitextParser;
use ParserOptions;
use ParserOutput;
use Title;

/**
 * Class AbstractTestContent
 *
 * @package MWUnit\ContentHandler
 */
abstract class AbstractTestContent extends \AbstractContent {
    private $text;

    /**
     * @inheritDoc
     */
    public function __construct( $text, $model_id = CONTENT_MODEL_TEST ) {
        parent::__construct( $model_id );

        $this->text = $text;
    }

    /**
     * @inheritDoc
     */
    public function getTextForSearchIndex() {
        return $this->text;
    }

    /**
     * @inheritDoc
     */
    public function getWikitextForTransclusion() {
        $allow_transclusion = MediaWikiServices::getInstance()->getMainConfig()->get( "MWUnitAllowTransclusion" );
        return $allow_transclusion ? $this->text : MWUnit::error( "mwunit-test-transclusion-error" );
    }

    /**
     * @inheritDoc
     */
    public function getTextForSummary( $maxLength = 250 ) {
        return $this->getSummaryText();
    }

    /**
     * @inheritDoc
     */
    public function getNativeData() {
        return $this->text;
    }

    /**
     * @inheritDoc
     */
    public function getSize() {
        return strlen( $this->text );
    }

    /**
     * @inheritDoc
     */
    public function copy() {
        return TestContent::newFromText( $this->text );
    }

    /**
     * @inheritDoc
     */
    public function isCountable( $hasLinks = null ) {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function fillParserOutput(
        Title $title,
        $revId,
        ParserOptions $options,
        $generateHtml,
        ParserOutput &$output
    ) {
        if ( !$generateHtml ) {
            return;
        }

        $tags = WikitextParser::getTestCasesFromWikitext( $this->text );
        $divs = [];

        foreach ( $tags as $tag ) {
            $content = $tag['content'];
            $attributes = $tag['attributes'];

            $this->fillHtmlFromTag( $html, $content, $attributes );

            $divs[] = $html;
        }

        $page = implode( "\n", $divs );
        $page = \Xml::tags( 'div', [ 'class' => 'mwunit-test-page' ], $page );

        $output->setText( $page );
    }

    /**
     * Returns the text of this page.
     *
     * @return string
     */
    protected function getText(): string {
        return $this->text;
    }

    /**
     * Returns the text used for the summary when a page is created
     * and no replacement summary was given.
     *
     * @return string
     */
    abstract function getSummaryText(): string;

    /**
     * Returns the HTML representation of the given <testcase> tag.
     *
     * @param string $html String to be filled with the HTML.
     * @param string $content
     * @param array $attributes
     * @return string
     */
    abstract function fillHtmlFromTag(&$html, string $content, array $attributes );
}