<?php

namespace MWUnit\ContentHandler;

use ConfigException;
use MediaWiki\MediaWikiServices;
use MWUnit\MWUnit;
use Xml;

/**
 * Class TestContent
 * @package MWUnit\ContentHandler
 */
class TestContent extends AbstractTestContent {
    private $mTests = [];

    /**
     * Creates a new TestContent object from the given $text.
     *
     * @param string $text
     * @return TestContent
     */
    public static function newFromText( string $text ) {
        return new self( $text );
    }

    /**
     * @inheritDoc
     */
    public function getSummaryText(): string {
        return "[tests]";
    }

    /**
     * @inheritDoc
     * @throws ConfigException
     */
    public function fillHtmlFromTag( &$html, string $content, array $attributes ) {
        $errors = [];

        MWUnit::areAttributesValid( $attributes, $errors );

        $name   = htmlspecialchars( $attributes['name'] ?? '[invalid]' ) ;
        $title  = MWUnit::testNameToSentence( $name );
        $header = $title . Xml::tags( 'hr', [], '' );

        if ( isset( $attributes['name'] ) ) {
            $mTest = $attributes['name'];

            if ( in_array( $mTest, $this->mTests ) ) {
                $errors[] = wfMessage( "mwunit-duplicate-test", $attributes['name'] );
            }

            $this->mTests[] = $mTest;
        }

        $class = $errors === [] ?
            'mwunit-test-case mwunit-valid successbox' :
            'mwunit-test-case mwunit-invalid errorbox';
        $body = $errors === [] ?
            $this->bodyFromAttributes( $content, $attributes ) :
            $this->bodyFromErrors( $errors );

        $html = Xml::tags(
            'div',
            [ 'class' => $class ],
            $header .
            Xml::tags( 'pre', [ 'class' => 'mwunit-body' ], $body )
        );
    }

    /**
     * @param string $content
     * @param array $attributes
     * @return string
     */
    private function bodyFromAttributes( string $content, array $attributes ) {
        $attributes = array_map( function ( $key, $value ): string {
            return sprintf( "@%s %s", htmlspecialchars( $key ), htmlspecialchars( $value ) );
        }, array_keys( $attributes ), $attributes );

        return implode( "\n", $attributes ) . "\n------\n" . htmlspecialchars( $content );
    }

    /**
     * @param array $errors
     * @return string
     */
    private function bodyFromErrors( array $errors ) {
        return wfMessage( 'mwunit-test-not-registered' ) . "\n------\n" . htmlspecialchars( implode( "\n", $errors ) );
    }
}