<?php

namespace MWUnit\ContentHandler;

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
     * @return bool
     */
    public function isValid() {
        $title = \RequestContext::getMain()->getTitle();

        if ( $title instanceof Title ) {
            return \RequestContext::getMain()->getTitle()->getNamespace() === NS_TEST;
        }

        return false;
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
        $href = \Title::newFromText( "Special:MWUnit" )->getFullURL( [ 'unitTestPage' => $title->getFullText() ] );

        $number_of_tests = count( $tags );

        $nav = $number_of_tests > 0 ? wfMessage( 'parentheses' )
            ->rawParams( \RequestContext::getMain()->getLanguage()->pipeList( [
                \Xml::tags( 'a', [ 'href' => $href ], wfMessage( "mwunit-nav-run-tests" ) )
            ] ) )->text() : '';
        $nav = wfMessage( "mwunit-no-tests", $number_of_tests )->plain() . " $nav";
        $nav = \Xml::tags('div', ['class' => 'mwunit-subtitle'], $nav );

        $page = \Xml::tags(
            "div",
            [ "class" => "mwunit-subtitle" ],
            $nav
        );

        $divs = [];

        foreach ( $tags as $tag ) {
            $content = $tag['content'];
            $attributes = $tag['attributes'];
            $this->fillHtmlFromTag( $html, $content, $attributes );
            $divs[] = $html;
        }

        $page .= \Xml::tags( 'div', [ 'class' => 'mwunit-test-page' ], implode( "\n", $divs ) );
        $output->setText( $page );
    }

    /**
     * Returns the text of this page.
     *
     * @return string
     */
    public function getText(): string {
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