<?php

namespace MWUnit\ContentHandler;

use ParserOptions;
use ParserOutput;
use Title;

class TestContent extends \AbstractContent {
    private $text;

    public function __construct($text, $model_id = CONTENT_MODEL_TEST ) {
        parent::__construct( $model_id );

        $this->text = $text;
    }

    public function fillParserOutput(
        Title $title,
        $revId,
        ParserOptions $options,
        $generateHtml,
        ParserOutput &$output
    ) {
        // TODO
    }

    public function getTextForSearchIndex() {
        return $this->text;
    }

    public function getWikitextForTransclusion() {
        return 'A test cannot be transcluded';
    }

    public function getTextForSummary( $maxLength = 250 ) {
        return $this->text;
    }

    public function getNativeData() {
        return $this->text;
    }

    public function getSize() {
        return strlen( $this->getNativeData() );
    }

    public function copy() {
        return clone $this;
    }

    public function isCountable( $hasLinks = null ) {
        return true;
    }
}