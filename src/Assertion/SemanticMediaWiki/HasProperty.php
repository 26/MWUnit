<?php

namespace MWUnit\Assertion\SemanticMediaWiki;

use MWUnit\Assertion\Assertion;

class HasProperty implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$property_name = trim( $frame->expand( $args[1] ) );
		$page = \SMWDIWikiPage::newFromTitle( \Title::newFromText( trim( $frame->expand( $args[0] ) ) ) );
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
		return 3;
	}
}
