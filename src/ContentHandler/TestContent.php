<?php

namespace MWUnit\ContentHandler;

use ConfigException;
use MediaWiki\MediaWikiServices;
use MWUnit\MWUnit;
use MWUnit\Renderer\Document;
use MWUnit\Renderer\Tag;
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

        $name   = $attributes['name'] ?? '[invalid]';
        $title  = MWUnit::testNameToSentence( $name );
        $header = new Tag( "span", new Document( [ new Tag( "span", $title ), new Tag( "hr", "" ) ] ) );

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
        $tag = $errors === [] ?
            $this->tagFromAttributes( $content, $attributes ) :
            $this->tagFromErrors( $errors );

        $html = new Tag(
            "div",
            new Document( [ $header, $tag ] ),
            [ "class" => $class ]
        );
    }

    /**
     * @param string $content
     * @param array $attributes
     * @return Tag
     */
    private function tagFromAttributes( string $content, array $attributes ): Tag {
        $attributes = array_map( function ( $key, $value ): string {
            return sprintf( "@%s %s", $key, $value );
        }, array_keys( $attributes ), $attributes );

        $text = implode( "\n", $attributes ) . "\n------\n" . $content;

        return new Tag( "pre", $text, [ 'class' => 'mwunit-body' ] );
    }

    /**
     * @param array $errors
     * @return Tag
     */
    private function tagFromErrors( array $errors ): Tag {
        $text = wfMessage( 'mwunit-test-not-registered' ) . "\n------\n" . implode( "\n", $errors );

        return new Tag( "pre", $text, [ 'class' => 'mwunit-body' ] );
    }
}