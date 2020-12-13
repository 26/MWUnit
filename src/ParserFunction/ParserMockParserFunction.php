<?php

namespace MWUnit\ParserFunction;

use MediaWiki\MediaWikiServices;
use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use MWUnit\ParserData;
use Parser;
use PPFrame;

class ParserMockParserFunction implements ParserFunction {
	/**
	 * @var Parser
	 */
	private $parser;

	private static $function_hook_backups = [];

	/**
	 * ParserMockParserFunction constructor.
	 *
	 * @param Parser|null $parser The Parser object to which the mocks will be added
	 */
	public function __construct( Parser $parser = null ) {
		$this->parser = $parser ?? MediaWikiServices::getInstance()->getParser();
	}

	/**
	 * Hooked to the #create_parser_mock parser function.
	 *
	 * @param ParserData $data
	 * @return string
	 */
	public function execute( ParserData $data ) {
		try {
			$parser_function = $data->getArgument( 0 );
		} catch ( \OutOfBoundsException $e ) {
			return MWUnit::error(
				"mwunit-create-parser-mock-missing-argument",
				[ "1st (parser function)" ]
			);
		}

		try {
			$data->setFlags( PPFrame::NO_ARGS | PPFrame::NO_IGNORE | PPFrame::NO_TAGS | PPFrame::NO_TEMPLATES );
			$content = $data->getArgument( 1 );
		} catch ( \OutOfBoundsException $e ) {
			return MWUnit::error(
				"mwunit-create-parser-mock-missing-argument",
				[ "2nd (mock content)" ]
			);
		}

		$reserved_functions = self::getReservedFunctions();

		if ( $reserved_functions === false ) {
			return MWUnit::error(
				"mwunit-create-parser-mock-reserved-function",
				[ $parser_function ]
			);
		}

		if ( in_array( $parser_function, $reserved_functions ) ) {
			return MWUnit::error(
				"mwunit-create-parser-mock-reserved-function",
				[ $parser_function ]
			);
		}

		if ( !$this->parserFunctionExists( $parser_function ) ) {
			return MWUnit::error(
				"mwunit-create-parser-mock-nonexistent-function",
				[ $parser_function ]
			);
		}

		$this->backupFunctionHook( $parser_function );
		$this->mockParserFunction( $parser_function, $content );

		return '';
	}

	/**
	 * Returns the array of reserved parser functions, or false on failure.
	 *
	 * @return array|false
	 */
	public static function getReservedFunctions() {
		$functions = [
			'create_mock',
			'create_parser_mock',
			'var_dump',
			'assert_string_contains',
			'assert_string_contains_ignore_case',
			'assert_has_length',
			'assert_empty',
			'assert_not_equals',
			'assert_equals',
			'assert_equals_ignore_case',
			'assert_file_exists',
			'assert_page_exists',
			'assert_greater_than',
			'assert_greater_than_or_equal',
			'assert_is_integer',
			'assert_is_numeric',
			'assert_less_than',
			'assert_less_than_or_equal',
			'assert_string_ends_with',
			'assert_string_starts_with',
			'assert_error',
			'assert_no_error',
			'assert_that',
			'assert_not_empty',
			'assert_has_property',
			'assert_property_has_value',
			'assert_expression'
		];

		try {
			\Hooks::run( "MWUnitGetReservedFunctions", [ &$functions ] );
		} catch ( \Exception $e ) {
			return false;
		}

		return $functions;
	}

	/**
	 * Returns true if and only if the given parser function name is registered (exists).
	 *
	 * @param string $parser_function The name of the parser function
	 * @return bool
	 */
	public function parserFunctionExists( string $parser_function ) {
		return in_array( $parser_function, $this->parser->getFunctionHooks() );
	}

	/**
	 * Backs up the given parser function's callback function.
	 *
	 * @param string $parser_function
	 */
	private function backupFunctionHook( string $parser_function ) {
		// We have already backed up this parser function's callback function.
		if ( isset( self::$function_hook_backups[ $parser_function ] ) ) {
			return;
		}

		$hooks    = $this->parser->mFunctionHooks;
		$callable = $hooks[ $parser_function ];

		self::$function_hook_backups[ $parser_function ] = $callable;
	}

	/**
	 * Mocks the given parser function with the given $mock_content.
	 *
	 * @param string $parser_function
	 * @param string $mock
	 */
	private function mockParserFunction( string $parser_function, string $mock ) {
		// Assert that the parser function was backed up
		assert( isset( self::$function_hook_backups[$parser_function] ) );

		$flags = $this->parser->mFunctionHooks[$parser_function][1];
		$this->parser->mFunctionHooks[$parser_function][0] = $flags & Parser::SFH_OBJECT_ARGS ?
			function ( \Parser $p, \PPFrame $f, array $args ) use ( $mock ) {
				$args = array_map( function ( $argument ) use ( $f ) {
					return trim( $f->expand( $argument ) );
				}, $args );

				$args = self::argsToTemplateArgs( $args );
				return [ $p->recursivePreprocess(
					$mock,
					$p->getPreprocessor()->newCustomFrame( $args )
				), 'isHTML' => false, 'noparse' => true ];
			} : function ( \Parser $p ) use ( $mock ) {
				$args = func_get_args();
				array_shift( $args );

				$args = self::argsToTemplateArgs( $args );
				return [ $p->recursivePreprocess(
					$mock,
					$p->getPreprocessor()->newCustomFrame( $args )
				), 'isHTML' => false, 'noparse' => true ];
			};
	}

	/**
	 * Processes elements in the given array of the form "$k=$v" to a key-value pair, and leaves
	 * 'regular' arguments as they are.
	 *
	 * "foo", "bar=quz", "foobar" would become:
	 *
	 * 0 => foo,
	 * bar => quz,
	 * 1 => foobar
	 *
	 * @param array $arguments
	 * @return array
	 */
	public function argsToTemplateArgs( array $arguments ) {
		$result = [];

		$index = 1;
		foreach ( $arguments as $v ) {
			$parts = explode( '=', $v );
			if ( count( $parts ) < 2 ) {
				$result[$index] = $v;
				$index++;
			} else {
				$k = array_shift( $parts );
				$result[ $k ] = implode( '=', $parts );
			}
		}

		return $result;
	}

	/**
	 * Restores the given parser function to the back up, or throws an MWUnitException is
	 * no backup exists for the parser function callable.
	 *
	 * @param string $parser_function
	 *
	 * @throws MWUnitException
	 */
	public static function restoreFunctionHook( string $parser_function ) {
		if ( !isset( self::$function_hook_backups[ $parser_function ] ) ) {
			MWUnit::getLogger()->error(
				"Unable to restore function hook for {function}, because it was never backed-up",
				[ "function" => $parser_function ]
			);

			throw new MWUnitException( "mwunit-exception-function-hook", [ $parser_function ] );
		}

		$parser = MediaWikiServices::getInstance()->getParser();
		$hook   = self::$function_hook_backups[ $parser_function ];

		$parser->mFunctionHooks[ $parser_function ] = $hook;
	}

	/**
	 * Restores all parser functions and resets this class.
	 *
	 * @throws MWUnitException
	 */
	public static function restoreAndReset() {
		foreach ( self::$function_hook_backups as $parser_function => $hook ) {
			self::restoreFunctionHook( $parser_function );
		}

		self::$function_hook_backups = [];
	}
}
