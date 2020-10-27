<?php

namespace MWUnit\ContentHandler;

use TextContentHandler;

class TestContentHandler extends TextContentHandler {
    /**
     * @inheritDoc
     */
    public function __construct( $modelId = CONTENT_MODEL_TEST ) {
        parent::__construct( $modelId, [ CONTENT_FORMAT_TEST ] );
    }

    /**
     * Returns the name of this content model.
     *
     * @return string
     */
    public function getModel() {
        return CONTENT_MODEL_TEST;
    }

    /**
     * @inheritDoc
     */
    public function getDiffEngineClass() {
        return TestContentDiffEngine::class;
    }

    /**
     * @inheritDoc
     */
    protected function getContentClass() {
        return TestContent::class;
    }

    /**
     * @inheritDoc
     */
    public function supportsCategories() {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function makeEmptyContent() {
        return $this->newTestContent();
    }

    /**
     * Creates a new TestContent object from the given $text.
     *
     * @param string|null $text
     * @return AbstractTestContent
     */
    public function newTestContent( $text = null ) {
        return TestContent::newFromText( $text ?: '' );
    }
}