<?php

namespace MWUnit\ContentHandler;

use CodeContentHandler;
use Title;

class TestContentHandler extends CodeContentHandler {
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
	 * @return TestContent
	 */
	public function newTestContent( $text = null ) {
		return TestContent::newFromText( $text ?: '' );
	}

	/**
	 * Only allow this content handler to be used in the Test namespace.
	 *
	 * @param Title $title
	 * @return bool
	 */
	public function canBeUsedOn( Title $title ) {
		if ( $title->getNamespace() !== NS_TEST ) {
			return false;
		}

		return parent::canBeUsedOn( $title );
	}
}
