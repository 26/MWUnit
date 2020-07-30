<?php

namespace MWUnit\Controller;

use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use Parser;
use PPFrame;

class ParserMockController {
	/**
	 * Array of parser function that cannot be mocked.
	 *
	 * @var array
	 */
	private static $reserved_functions = [
		'create_mock',
		'create_parser_mock',
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
		'assert_property_has_value'
	];

	private static $function_hook_backups = [];

	/**
	 * Hooked to the #create_parser_mock parser function.
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 * @return string
	 */
	public static function handleCreateMock( Parser $parser, PPFrame $frame, array $args ) {
		if ( !isset( $args[0] ) ) {
			return MWUnit::error(
				"mwunit-create-parser-mock-missing-argument",
				[ "1st (parser function)" ]
			);
		}

		if ( !isset( $args[1] ) ) {
			return MWUnit::error(
				"mwunit-create-parser-mock-missing-argument",
				[ "2nd (mock content)" ]
			);
		}

		$parser_function = trim( $frame->expand( $args[0] ) );
		$mock_content = trim(
			$frame->expand(
				$args[1],
				PPFrame::NO_ARGS & PPFrame::NO_IGNORE & PPFrame::NO_TAGS & PPFrame::NO_TEMPLATES
			)
		);

		if ( in_array( $parser_function, self::getReservedFunctions() ) ) {
			return MWUnit::error(
				"mwunit-create-parser-mock-reserved-function",
				[ $parser_function ]
			);
		}

		if ( !self::parserFunctionExists( $parser_function ) ) {
			return MWUnit::error(
				"mwunit-create-parser-mock-nonexistent-function",
				[ $parser_function ]
			);
		}

		self::backupFunctionHook( $parser_function );
		self::mockParserFunction( $parser_function, $mock_content );

		return '';
	}

	/**
	 * Returns the array of reserved parser functions.
	 *
	 * @return array
	 * @throws \FatalError
	 * @throws \MWException
	 */
	public static function getReservedFunctions() {
		$functions = self::$reserved_functions;
		\Hooks::run( "MWUnitGetReservedFunctions", [ &$functions ] );

		return $functions;
	}

	/**
	 * Returns true if and only if the given parser function name is registered (exists).
	 *
	 * @param string $parser_function The name of the parser function
	 * @return bool
	 */
	public static function parserFunctionExists( string $parser_function ) {
		$parser = \MediaWiki\MediaWikiServices::getInstance()->getParser();
		return in_array( $parser_function, $parser->getFunctionHooks() );
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

	public static function restoreFunctionHook( string $parser_function ) {
		if ( !isset( self::$function_hook_backups[ $parser_function ] ) ) {
			\MWUnit\MWUnit::getLogger()->error(
				"Unable to restore function hook for {function}, because it was never backed-up",
				[ "function" => $parser_function ]
			);

			throw new MWUnitException( 'Could not restore function hook for ' . $parser_function );
		}

		$parser = \MediaWiki\MediaWikiServices::getInstance()->getParser();
		$hook   = self::$function_hook_backups[ $parser_function ];

		$parser->mFunctionHooks[ $parser_function ] = $hook;
	}

	/**
	 * Backs up the given parser function's callback function.
	 *
	 * @param string $parser_function
	 */
	private static function backupFunctionHook( string $parser_function ) {
		$parser = \MediaWiki\MediaWikiServices::getInstance()->getParser();
		$hooks  = $parser->mFunctionHooks;

		self::$function_hook_backups[ $parser_function ] = $hooks[ $parser_function ];
	}

	/**
	 * Mocks the given parser function with the given $mock_content.
	 *
	 * @param string $parser_function
	 * @param string $mock_content
	 */
	private static function mockParserFunction( string $parser_function, string $mock_content ) {
		// Assert that the parser function was backed up
		assert( isset( self::$function_hook_backups[$parser_function] ) );

		$parser = \MediaWiki\MediaWikiServices::getInstance()->getParser();

		$callback =& $parser->mFunctionHooks[$parser_function][0];
		$flags = $parser->mFunctionHooks[$parser_function][1];

		if ( $flags & Parser::SFH_OBJECT_ARGS ) {
			$callback = function ( \Parser $p, \PPFrame $f, array $args ) use ( $mock_content ) {
				$args = array_map( function ( $argument ) use ( $f ) {
					return trim( $f->expand( $argument ) );
				}, $args );

				$args = self::argsToTemplateArgs( $args );

				return [ $p->recursivePreprocess(
					$mock_content,
					$p->getPreprocessor()->newCustomFrame( $args )
				), 'isHTML' => false, 'noparse' => true ];
			};
		} else {
			$callback = function ( \Parser $p ) use ( $mock_content ) {
				$args = func_get_args();
				array_shift( $args );

				$args = self::argsToTemplateArgs( $args );

				return [ $p->recursivePreprocess(
					$mock_content,
					$p->getPreprocessor()->newCustomFrame( $args )
				), 'isHTML' => false, 'noparse' => true ];
			};
		}
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
	public static function argsToTemplateArgs( array $arguments ) {
		$result = [];

		foreach ( $arguments as $v ) {
			$parts = explode( '=', $v );
			if ( count( $parts ) < 2 ) {
				array_push( $result, $v );
			} else {
				$k = array_shift( $parts );
				$result[ $k ] = implode( '=', $parts );
			}
		}

		return $result;
	}
}
