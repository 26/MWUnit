<?php


namespace MWUnit\Factory;

use MWUnit\MWUnit;
use MWUnit\ParserData;
use MWUnit\ParserTag\TestCaseParserTag;
use Parser;
use PPFrame;

/**
 * Class TagFactory
 *
 * @package MWUnit
 */
class TagFactory {
    protected $parser;

    /**
     * TagFactory constructor.
     *
     * @param Parser|null $parser
     */
    public function __construct( Parser $parser = null ) {
        $this->parser = $parser;
    }

    /**
     * Convenience instantiation of the TagFactory class.
     *
     * @param Parser $parser
     * @return TagFactory
     */
    public static function newFromParser( Parser $parser ): TagFactory {
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
            list( $name, $definition ) = $this->getTestCaseFunctionDefinition();
            $this->parser->setHook( $name, $definition );
        } catch( \MWException $e ) {
            MWUnit::getLogger()->critical( "Unable to register 'testcase' tag function: {e}", [
                'e' => $e->getMessage()
            ] );
        }
    }

    private function getTestCaseFunctionDefinition() {
        $definition = function( $input, array $args, Parser $parser, PPFrame $frame ) {
            $parser_function = $this->newTestCaseParserTag();

            $parser_data = new ParserData( $parser, $frame, $args );
            $parser_data->setInput( $input );

            return $parser_function->execute( $parser_data );
        };

        return [ 'testcase', $definition ];
    }

    private function newTestCaseParserTag() {
        return new TestCaseParserTag();
    }
}