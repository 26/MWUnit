<?php

namespace MWUnit\ParserFunction;

use MWUnit\ParserData;
use MWUnit\Runner\TestRun;
use MWUnit\TestRunInjector;

/**
 * Class VarDumpParserFunction
 *
 * The class for the "var_dump" ParserFunction.
 *
 * @package MWUnit\ParserFunction
 */
class VarDumpParserFunction implements ParserFunction, TestRunInjector {
	/**
	 * @var TestRun
	 */
	private static $run;

	/**
	 * @inheritDoc
	 */
	public static function setTestRun( TestRun $run ) {
		self::$run = $run;
	}

	/**
	 * Hooked to the #var_dump parser function.
	 *
	 * @param ParserData $data
	 * @return string
	 */
	public function execute( ParserData $data ) {
		if ( self::$run ) {
			$value = $data->getArgument( 0 );
			$formatted_value = $this->formatDump( $value );

			self::$run->test_outputs[] = $formatted_value;
		}

		return '';
	}

	/**
	 * Formats the given variable into a "var_dump" format.
	 *
	 * @param string $variable
	 * @return string
	 */
	public function formatDump( string $variable ): string {
		if ( empty( $variable ) ) {
			return "NULL";
		}

		if ( ctype_digit( $variable ) ) {
			return sprintf( "int(%d)", $variable );
		}

		if ( is_numeric( $variable ) ) {
			return sprintf( "float(%s)", $variable );
		}

		if ( preg_match( "/^\d+(,)\d+$/", $variable ) ) {
			return sprintf( "float(%s)", (float)str_replace( ",", ".", $variable ) );
		}

		return sprintf( "string(%d) \"%s\"", strlen( $variable ), $variable );
	}
}
