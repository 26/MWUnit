<?php

namespace MWUnit\Assertion\SemanticMediaWiki;

use MWUnit\Assertion\Assertion;

/**
 * Class SMWAssertion
 *
 * Assertions extending this class only get loaded if Semantic MediaWiki is installed.
 *
 * @package MWUnit\Assertion\SemanticMediaWiki
 */
abstract class SMWAssertion implements Assertion {
	/**
	 * @inheritDoc
	 */
	public static function shouldRegister(): bool {
		return \ExtensionRegistry::getInstance()->isLoaded( 'SemanticMediaWiki' );
	}
}
