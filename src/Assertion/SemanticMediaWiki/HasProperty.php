<?php

namespace MWUnit\Assertion\SemanticMediaWiki;

use MWUnit\Assertion\Assertion;

class HasProperty implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$title = \Title::newFromText( trim( $frame->expand( $args[0] ) ) );
		if ( $title === null || $title === false || !$title->exists() ) {
			$failure_message = wfMessage( "mwunit-invalid-page-name" )->plain();
			return null;
		}

		$property_name = trim( $frame->expand( $args[1] ) );
		$page = \SMWDIWikiPage::newFromTitle( $title );
		$store = \SMW\StoreFactory::getStore();
		$data = $store->getSemanticData( $page );
		$property = \SMWDIProperty::newFromUserLabel( $property_name );
		$values = $data->getPropertyValues( $property );

		$failure_message = isset( $args[2] ) ?
			trim( $frame->expand( $args[2] ) ) :
			sprintf(
				wfMessage( "mwunit-assert-failure-has-property",
					$page->getTitle()->getText(),
					$property_name
				)->plain()
			);

		return count( $values ) > 0;
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}
}
