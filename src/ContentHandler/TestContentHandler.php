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
    protected function getContentClass() {
        return TestContent::class;
    }

    /**
     * @inheritDoc
     */
    public function supportsSections() {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function supportsRedirects() {
        return false;
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
    public function isParserCacheSupported() {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function makeEmptyContent() {
        return new TestContent( '' );
    }
}