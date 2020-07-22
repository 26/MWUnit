<?php

namespace MWUnit\Registry;

use MWUnit\Assertion\Assertion;
use MWUnit\Assertion\Equals;
use MWUnit\Assertion\EqualsIgnoreCase;
use MWUnit\Assertion\Error;
use MWUnit\Assertion\GreaterThan;
use MWUnit\Assertion\GreaterThanOrEqual;
use MWUnit\Assertion\HasLength;
use MWUnit\Assertion\IsEmpty;
use MWUnit\Assertion\IsInteger;
use MWUnit\Assertion\IsNumeric;
use MWUnit\Assertion\LessThan;
use MWUnit\Assertion\LessThanOrEqual;
use MWUnit\Assertion\NoError;
use MWUnit\Assertion\NotEmpty;
use MWUnit\Assertion\PageExists;
use MWUnit\Assertion\SemanticMediaWiki\HasProperty;
use MWUnit\Assertion\SemanticMediaWiki\PropertyHasValue;
use MWUnit\Assertion\StringContains;
use MWUnit\Assertion\StringContainsIgnoreCase;
use MWUnit\Assertion\StringEndsWith;
use MWUnit\Assertion\StringStartsWith;
use MWUnit\Assertion\That;
use MWUnit\Controller\AssertionController;
use Parser;
use PPFrame;

class AssertionRegistry {
	/**
	 * The parser to which to register the assertions.
	 *
	 * @var Parser
	 */
	private $parser;
	private $classes;

	private static $instance = null;

	private function __construct() {
		$classes = [
			Equals::class,
			EqualsIgnoreCase::class,
			Error::class,
			GreaterThan::class,
			GreaterThanOrEqual::class,
			HasLength::class,
			IsEmpty::class,
			IsInteger::class,
			IsNumeric::class,
			LessThan::class,
			LessThanOrEqual::class,
			NoError::class,
			NotEmpty::class,
			PageExists::class,
			StringContains::class,
			StringContainsIgnoreCase::class,
			StringEndsWith::class,
			StringStartsWith::class,
			That::class,
			HasProperty::class,
			PropertyHasValue::class
		];

		\Hooks::run( "MWUnitGetAssertionClasses", [ &$classes ] );

		$this->classes = $classes;
		$this->parser = \MediaWiki\MediaWikiServices::getInstance()->getParser();
	}

	/**
	 * Gets the instance of the AssertionRegistry.
	 *
	 * @return AssertionRegistry
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers the assertion classes to the parser object.
	 */
	public function registerAssertionClasses() {
		$assertions = array_filter( $this->classes, function ( $class ): bool {
			$reflection_class = new \ReflectionClass( $class );
			return $reflection_class->implementsInterface( Assertion::class );
		} );

		$registering_classes = array_filter( $assertions, function ( $class ): bool {
			return $class::shouldRegister();
		} );

		foreach ( $registering_classes as $assertion ) {
			$this->registerAssertionClass( $assertion );
		}
	}

	/**
	 * Registers the given assertion class to the parser.
	 *
	 * @param $assertion
	 * @return bool True on success, false on failure
	 * @throws \MWException
	 */
	public function registerAssertionClass( $assertion ) {
		try {
			$reflection_class = new \ReflectionClass( $assertion );

			if ( !$reflection_class->implementsInterface( Assertion::class ) ) {
				return false;
			}
		} catch ( \ReflectionException $e ) {
			return false;
		}

		$assertion_name = $assertion::getName();
		$this->parser->setFunctionHook(
			"assert_$assertion_name",
			function ( Parser $parser, PPFrame $frame, $args ) use ( $assertion ) {
				return AssertionController::handleAssertionParserHook(
					$parser,
					$frame,
					$args,
					$assertion
				);
			},
			Parser::SFH_OBJECT_ARGS
		);

		return true;
	}
}