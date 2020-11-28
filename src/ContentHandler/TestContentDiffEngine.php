<?php

namespace MWUnit\ContentHandler;

use Content;
use DifferenceEngine;

class TestContentDiffEngine extends DifferenceEngine {
	/**
	 * @inheritDoc
	 */
	public function generateContentDiffBody( Content $old, Content $new ) {
		$text_old = $old->getNativeData();
		$text_new = $new->getNativeData();

		return $this->generateTextDiffBody( $text_old, $text_new );
	}
}
