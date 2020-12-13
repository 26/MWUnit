<?php

namespace MWUnit\Factory;

use MWUnit\Assertion\Equals;
use MWUnit\Assertion\EqualsIgnoreCase;
use MWUnit\Assertion\Error;
use MWUnit\Assertion\Expressions\Expression;
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
use MWUnit\MWUnit;
use MWUnit\ParserData;
use MWUnit\ParserFunction\AssertionParserFunction;
use MWUnit\ParserFunction\ParserFunction;
use Parser;
use PPFrame;

/**
 * Class AssertionFactory
 *
 * @package MWUnit
 */
class AssertionFactory extends ParserFunctionFactory {
	public static $classes = [
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
		PropertyHasValue::class,
		Expression::class
	];

	/**
	 * Convenience instantiation of the ParserFunctionFactory class.
	 *
	 * @param Parser $parser
	 * @return AssertionFactory
	 */
	public static function newFromParser( Parser $parser ): ParserFunctionFactory {
		return new self( $parser );
	}

	/**
	 * Filters the given array of Assertion class names and
	 * returns only the Assertions class names that are enabled.
	 *
	 * @param string[] $classes
	 * @return string[]
	 */
	public static function filterEnabledAssertions( array $classes ) {
		return array_filter( $classes, function ( $class ): bool {
			return $class::shouldRegister();
		} );
	}

	/**
	 * Returns all assertion classes as an array.
	 *
	 * @return string[] Array of Assertion class names
	 */
	public static function getAssertionClasses(): array {
		try {
			\Hooks::run( "MWUnitGetAssertionClasses", [ &self::$classes ] );
		} catch ( \Exception $e ) {
			MWUnit::getLogger()->error( "MWUnitGetAssertionClasses hook failed: {failure}", [
				'failure' => $e->getMessage()
			] );
		}

		return self::$classes;
	}

	/**
	 * Registers the function handlers.
	 */
	public function registerFunctionHandlers() {
		$classes = self::getAssertionClasses();
		$register_assertions = self::filterEnabledAssertions( $classes );

		foreach ( $register_assertions as $class ) {
			$this->registerAssertionClass( $class );
		}
	}

	/**
	 * Registers the given assertion class to the parser.
	 *
	 * @param string $assertion The assertion class name
	 */
	public function registerAssertionClass( string $assertion ) {
		MWUnit::getLogger()->notice( "Registering assertion {assertion}", [
			"assertion" => $assertion::getName()
		] );

		try {
			list( $name, $definition, $flag ) = $this->getAssertionFunctionDefinition( $assertion );
			$this->parser->setFunctionHook( $name, $definition, $flag );
		} catch ( \MWException $e ) {
			MWUnit::getLogger()->critical( "Unable to register 'assert_{name}' parser function: {e}", [
				'name'  => $assertion::getName(),
				'e'     => $e->getMessage()
			] );
		}
	}

	/**
	 * @param string $assertion
	 * @return array
	 */
	public function getAssertionFunctionDefinition( string $assertion ) {
		$definition = function ( Parser $parser, PPFrame $frame, $args ) use ( $assertion ) {
			if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
				return MWUnit::error( "mwunit-outside-test-namespace" );
			}

			$assertion_parser_function = $this->newAssertionParserFunction( $assertion );
			$parser_data = new ParserData( $parser, $frame, $args );

			return $assertion_parser_function->execute( $parser_data );
		};

		return [ 'assert_' . $assertion::getName(), $definition, Parser::SFH_OBJECT_ARGS ];
	}

	/**
	 * @param string $assertion
	 * @return AssertionParserFunction
	 */
	private function newAssertionParserFunction( string $assertion ): ParserFunction {
		return AssertionParserFunction::newFromClass( $assertion );
	}
}
