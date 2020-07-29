<?php

namespace MWUnit\Assertion\SemanticMediaWiki;

use MWUnit\Assertion\Assertion;

class PropertyHasValue implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "property_has_value";
	}

	/**
	 * @inheritDoc
	 */
	public static function shouldRegister(): bool {
		return \ExtensionRegistry::getInstance()->isLoaded( 'SemanticMediaWiki' );
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
	public static function assert( &$failure_message, $page_title, $property_name, $expected_value, $message = null ) {
		$title = \Title::newFromText( $page_title );

		if ( $title === null || $title === false || !$title->exists() ) {
			$failure_message = wfMessage( "mwunit-invalid-page-name" )->plain();
			return null;
		}

		$page = \SMWDIWikiPage::newFromTitle( $title );
		$store = \SMW\StoreFactory::getStore();
		$data = $store->getSemanticData( $page );
		$property = \SMWDIProperty::newFromUserLabel( $property_name );
		$values = $data->getPropertyValues( $property );

		$failure_message = $message ??
			sprintf(
				wfMessage( "mwunit-assert-failure-property-has-value",
					$property_name,
					$expected_value,
					$page->getTitle()->getText()
				)->plain()
			);

		return count( array_filter( $values, function ( \SMW\DIWikiPage $value ) use ( $expected_value ) {
			return $value->getDBkey() === $expected_value;
		} ) ) > 0;
	}
}
