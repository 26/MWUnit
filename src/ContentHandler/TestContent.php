<?php

namespace MWUnit\ContentHandler;

use MediaWiki\MediaWikiServices;
use MWUnit\MWUnit;
use MWUnit\Renderer\Document;
use MWUnit\Renderer\Tag;
use MWUnit\TestCase;
use MWUnit\TestClass;
use MWUnit\WikitextParser;
use ParserOptions;
use ParserOutput;
use Title;

/**
 * Class AbstractTestContent
 *
 * @package MWUnit\ContentHandler
 */
class TestContent extends \AbstractContent {
    private $text;

    /**
     * Creates a new TestContent object from the given $text.
     *
     * @param string $text
     * @return TestContent
     */
    public static function newFromText( string $text ) {
        return new self( $text );
    }

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
    public function getTextForSummary( $max_length = 250 ) {
        $text = $this->getNativeData();
        $suffix = "";

        if ( strlen( $text ) > $max_length - 3 ) {
            $suffix = "...";
        }

        return mb_substr( $this->getNativeData(), 0, $max_length - 3 ) . $suffix;
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

        // TODO
    }
}