<?php

namespace MWUnit\Assertion\SemanticMediaWiki;

use Title;

class PropertyHasValue extends SMWAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "property_has_value";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 3;
	}

	/**
	 * Returns false if and only if the property given by $property_name on the page given
	 * by $page_title does not have the value given by $expected_value.
	 *
	 * @param string &$failure_message
	 * @param string $page_title
	 * @param string $property_name
	 * @param string $expected_value
	 * @param string|null $message
	 * @return bool|null
	 */
	public static function assert( string &$failure_message, string $page_title, string $property_name, string $expected_value, $message = null ) {
		$title = Title::newFromText( $page_title );

		// Title doesn't exist
		if ( !$title instanceof Title || !$title->exists() ) {
			$failure_message = wfMessage( "mwunit-invalid-assertion" )->plain();
			return null;
		}

		// Create a new SMW WikiPage from $title
		$page = \SMWDIWikiPage::newFromTitle( $title );

		// Get the Store singleton
		$store = \SMW\StoreFactory::getStore();

		// Get all data associated with the previously mentioned page
		$data = $store->getSemanticData( $page );

		// Create a new SMW property object from $property_name
		$property = \SMWDIProperty::newFromUserLabel( $property_name );

		// Get the values from the property
		$values = $data->getPropertyValues( $property );

		// Filter out all the properties that do not equal the expected value
		$valid_properties = array_filter( $values, function ( $value ) use ( $expected_value ) {
			return $value->getSortKey() === $expected_value;
		} );

		// Check if there are any properties left that did met the expected value
		$property_has_value = count( $valid_properties ) > 0;

		$default_failure_message = wfMessage(
			"mwunit-assert-failure-property-has-value",
			$property_name,
			$expected_value,
			$page->getTitle()->getText()
		)->plain();

		$failure_message = $message ?? $default_failure_message;

		return $property_has_value;
	}
}
