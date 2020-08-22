<?php

namespace MWUnit\ParserFunction;

use MWUnit\Exception\MWUnitException;
use MWUnit\Injector\TestRunInjector;
use MWUnit\MWUnit;
use MWUnit\Output\StringOutput;
use MWUnit\ParserData;
use MWUnit\Runner\TestRun;
use Parser;
use PPFrame;

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
        if ( $data->getParser()->getTitle()->getNamespace() !== NS_TEST ) {
            return MWUnit::error( "mwunit-outside-test-namespace" );
        }

        if ( !MWUnit::isRunning() ) {
            return '';
        }

        if ( !self::$run ) {
            return '';
        }

        $value = $data->getArgument( 0 );
        $formatted_value = $this->formatDump( $value );

        $test_output = new StringOutput( $formatted_value );
        self::$run->getTestOutputCollector()->append( $test_output );

        return '';
    }

    public function formatDump( string $variable ): string {
        $length = strlen( $variable );
        $value  = htmlentities( $variable );

        return sprintf( 'string(%d) "%s"', $length, $value );
    }
}