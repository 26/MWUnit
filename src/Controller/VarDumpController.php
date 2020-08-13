<?php

namespace MWUnit\Controller;

use MWUnit\Debug\StringOutput;
use MWUnit\Exception\MWUnitException;
use MWUnit\Injector\TestRunInjector;
use MWUnit\MWUnit;
use MWUnit\Runner\TestRun;
use Parser;
use PPFrame;

class VarDumpController implements TestRunInjector {
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
     * @param Parser $parser
     * @param PPFrame $frame
     * @param array $args
     * @return string
     */
    public static function handleVarDump( Parser $parser, PPFrame $frame, array $args ) {
        if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
            return MWUnit::error( "mwunit-outside-test-namespace" );
        }

        if ( !MWUnit::isRunning() ) {
            return '';
        }

        if ( !self::$run ) {
            return '';
        }

        $variable = trim( $frame->expand( $args[0] ) );
        $formatted_value = self::formatDump( $variable );

        try {
            $test_output = new StringOutput( $formatted_value );
        } catch( MWUnitException $e ) {
            return '';
        }

        self::$run->getTestOutputCollector()->append( $test_output );

        return '';
    }

    public static function formatDump( string $variable ): string {
        $length = strlen( $variable );
        $value  = htmlentities( $variable );

        return sprintf( 'string(%d) "%s"', $length, $value );
    }
}