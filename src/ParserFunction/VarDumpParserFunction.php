<?php

namespace MWUnit\ParserFunction;

use MWUnit\Exception\MWUnitException;
use MWUnit\ParserData;
use MWUnit\Runner\TestRun;
use MWUnit\TestRunInjector;

class VarDumpParserFunction implements ParserFunction, TestRunInjector {
	/**
	 * @var TestRun
	 */
	private static $run;

	public static function setTestRun( TestRun $run ) {
		self::$run = $run;
	}

	/**
	 * Hooked to the #var_dump parser function.
	 *
	 * @param ParserData $data
	 * @return string
	 * @throws MWUnitException
	 */
	public function execute( ParserData $data ) {
		if ( !self::$run ) {
			return '';
		}

		$value = $data->getArgument( 0 );
		$formatted_value = $this->formatDump( $value );

		self::$run->test_outputs[] = $formatted_value;

		return '';
	}

	public function formatDump( string $variable ): string {
		$type = $this->determineVariableType( $variable );

		switch ( $type ) {
			case "empty":
				return "NULL";
			case "int":
			case "float":
				return sprintf( '%s(%d)', $type, $variable );
			default:
				$length = strlen( $variable );
				return sprintf( '%s(%d) "%s"', $type, $length, $variable );
		}
	}

	public function determineVariableType( string $variable ): string {
		if ( empty( $variable ) ) {
			return "empty";
		}

		if ( ctype_digit( $variable ) ) {
			return "int";
		}

		if ( is_numeric( $variable ) ) {
			return "float";
		}

		return "string";
	}
}
