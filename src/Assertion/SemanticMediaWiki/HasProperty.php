<?php

namespace MWUnit\Assertion\SemanticMediaWiki;

use SMW\StoreFactory;
use SMWDIProperty;
use SMWDIWikiPage;

class HasProperty extends SMWAssertion {
	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return "has_property";
	}

	/**
	 * @inheritDoc
	 */
	public static function getRequiredArgumentCount(): int {
		return 2;
	}

	/**
	 * Returns false if and only if the page given by $page_title does not have the property
	 * given by $property_name.
	 *
	 * @param string &$failure_message
	 * @param string $page_title
	 * @param string $property_name
	 * @param string|null $message
	 * @return bool|null
	 */
	public static function assert( string &$failure_message, string $page_title, string $property_name, $message = null ) {
		$title = \Title::newFromText( $page_title );
		if ( $title === null || $title === false || !$title->exists() ) {
			$failure_message = wfMessage( "mwunit-invalid-assertion" )->plain();
			return null;
		}

		$page = SMWDIWikiPage::newFromTitle( $title );
		$store = StoreFactory::getStore();
		$data = $store->getSemanticData( $page );
		$property = SMWDIProperty::newFromUserLabel( $property_name );
		$values = $data->getPropertyValues( $property );

		$failure_message = $message ??
			sprintf(
				wfMessage( "mwunit-assert-failure-has-property",
					$page->getTitle()->getText(),
					$property_name
				)->plain()
			);

		return count( $values ) > 0;
	}
}
