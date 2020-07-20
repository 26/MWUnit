<?php

namespace MWUnit\Assertion\SemanticMediaWiki;

use MWUnit\Assertion\Assertion;

class PropertyHasValue implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function assert( \Parser $parser, \PPFrame $frame, array $args, &$failure_message ) {
		$property_name = trim( $frame->expand( $args[1] ) );
		$expected_value = trim( $frame->expand( $args[2] ) );

		$page = \SMWDIWikiPage::newFromTitle( \Title::newFromText( trim( $frame->expand( $args[0] ) ) ) );
		$store = \SMW\StoreFactory::getStore();
		$data = $store->getSemanticData( $page );
		$property = \SMWDIProperty::newFromUserLabel( $property_name );
		$values = $data->getPropertyValues( $property );

		$failure_message = isset( $args[3] ) ?
			trim( $frame->expand( $args[3] ) ) :
			sprintf(
				wfMessage( "mwunit-assert-failure-property-has-value",
					$property_name,
					$expected_value,
					$page->getTitle()->getText()
				)->plain()
			);

		return count( array_filter( $values, function( \SMW\DIWikiPage $value ) use ( $expected_value ) {
			return $value->getDBkey() === $expected_value;
		} ) ) > 0;
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}
}
