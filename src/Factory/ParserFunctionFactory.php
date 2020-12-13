<?php

namespace MWUnit\Factory;

use MWUnit\MWUnit;
use MWUnit\ParserData;
use MWUnit\ParserFunction\ParserFunction;
use MWUnit\ParserFunction\ParserMockParserFunction;
use MWUnit\ParserFunction\TemplateMockParserFunction;
use MWUnit\ParserFunction\VarDumpParserFunction;
use Parser;
use PPFrame;

class ParserFunctionFactory {
	protected $parser;

	/**
	 * ParserFunctionFactory constructor.
	 *
	 * @param Parser|null $parser
	 */
	public function __construct( Parser $parser = null ) {
		$this->parser = $parser;
	}

	/**
	 * Convenience instantiation of the ParserFunctionFactory class.
	 *
	 * @param Parser $parser
	 * @return ParserFunctionFactory
	 */
	public static function newFromParser( Parser $parser ): ParserFunctionFactory {
		return new self( $parser );
	}

	/**
	 * Sets the Parser.
	 *
	 * @param Parser $parser
	 */
	public function setParser( Parser $parser ) {
		$this->parser = $parser;
	}

	/**
	 * Registers the function handlers.
	 */
	public function registerFunctionHandlers() {
		try {
			list( $name, $definition, $flag ) = $this->getParserMockFunctionDefinition();
			$this->parser->setFunctionHook( $name, $definition, $flag );
		} catch ( \MWException $e ) {
			MWUnit::getLogger()->critical( "Unable to register 'create_parser_mock' parser function: {e}", [
				'e' => $e->getMessage()
			] );
		}

		try {
			list( $name, $definition, $flag ) = $this->getTemplateMockFunctionDefinition();
			$this->parser->setFunctionHook( $name, $definition, $flag );
		} catch ( \MWException $e ) {
			MWUnit::getLogger()->critical( "Unable to register 'create_mock' parser function: {e}", [
				'e' => $e->getMessage()
			] );
		}

		try {
			list( $name, $definition, $flag ) = $this->getVarDumpFunctionDefinition();
			$this->parser->setFunctionHook( $name, $definition, $flag );
		} catch ( \MWException $e ) {
			MWUnit::getLogger()->critical( "Unable to register 'var_dump' parser function: {e}", [
				'e' => $e->getMessage()
			] );
		}

		$assertion_factory = AssertionFactory::newFromParser( $this->parser );
		$assertion_factory->registerFunctionHandlers();
	}

	/**
	 * Returns the parser function definition for the "#create_parser_mock" parser function.
	 *
	 * @return array
	 */
	private function getParserMockFunctionDefinition(): array {
		$definition = function ( Parser $parser, PPFrame $frame, $args ) {
			if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
				return MWUnit::error( "mwunit-outside-test-namespace" );
			}

			$parser_function = $this->newParserMockParserFunction();
			$parser_data = new ParserData( $parser, $frame, $args );

			return $parser_function->execute( $parser_data );
		};

		return [ 'create_parser_mock', $definition, Parser::SFH_OBJECT_ARGS ];
	}

	/**
	 * Returns the function definition for the "#create_mock" parser function.
	 *
	 * @return array
	 */
	private function getTemplateMockFunctionDefinition(): array {
		$definition = function ( Parser $parser, PPFrame $frame, $args ) {
			if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
				return MWUnit::error( "mwunit-outside-test-namespace" );
			}

			$parser_function = $this->newTemplateMockParserFunction();
			$parser_data = new ParserData( $parser, $frame, $args );

			return $parser_function->execute( $parser_data );
		};

		return [ 'create_mock', $definition, Parser::SFH_OBJECT_ARGS ];
	}

	/**
	 * Returns the function definition for the "#var_dump" parser function.
	 *
	 * @return array
	 */
	private function getVarDumpFunctionDefinition(): array {
		$definition = function ( Parser $parser, PPFrame $frame, $args ) {
			if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
				return MWUnit::error( "mwunit-outside-test-namespace" );
			}

			$parser_function = $this->newVarDumpParserFunction();
			$parser_data = new ParserData( $parser, $frame, $args );

			return $parser_function->execute( $parser_data );
		};

		return [ 'var_dump', $definition, Parser::SFH_OBJECT_ARGS ];
	}

	/**
	 * Returns a new "ParserMockParserFunction" object.
	 *
	 * @return ParserFunction
	 */
	private function newParserMockParserFunction(): ParserFunction {
		return new ParserMockParserFunction();
	}

	/**
	 * Returns a new "TemplateMockParserFunction" object.
	 *
	 * @return ParserFunction
	 */
	private function newTemplateMockParserFunction(): ParserFunction {
		return new TemplateMockParserFunction();
	}

	/**
	 * Returns a new "VarDumpParserFunction" object.
	 *
	 * @return ParserFunction
	 */
	private function newVarDumpParserFunction(): ParserFunction {
		return new VarDumpParserFunction();
	}
}
