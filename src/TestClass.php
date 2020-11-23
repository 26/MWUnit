<?php

namespace MWUnit;

use DOMDocument;
use DOMNode;
use Exception;
use Revision;
use Title;

class TestClass {
    /**
     * @var string
     */
    private $setup;

    /**
     * @var string
     */
    private $teardown;

    /**
     * @var TestCase[]
     */
    private $test_cases;

    /**
     * @var Title
     */
    private $title;

    /**
     * Creates a new TestClass object from the given wikipage.
     *
     * @param \WikiPage $wikipage
     * @return TestClass
     */
    public static function newFromWikipage( \WikiPage $wikipage ): TestClass {
        function getTagAttributes( DOMNode $dom_node ): array {
            if ( !$dom_node->hasAttributes() ) {
                return [];
            }

            $attributes     = [];
            $dom_attributes = $dom_node->attributes;

            foreach ( $dom_attributes as $attribute ) {
                $name       = $attribute->name;
                $content    = $attribute->textContent;

                $attributes[$name] = $content;
            }

            return $attributes;
        }

        $content = $wikipage->getContent( Revision::FOR_THIS_USER );
        $wikitext = $wikipage->getContentHandler()->serializeContent( $content );

        $dom = new DOMDocument();
        @$dom->loadHTML( $wikitext );

        $setup_tags = $dom->getElementsByTagName( "setup" );
        $teardown_tags = $dom->getElementsByTagName( "teardown" );
        $test_case_tags = $dom->getElementsByTagName( "testcase" );

        $setup = "";

        foreach ( $setup_tags as $setup_tag ) {
            $setup .= trim( $setup_tag->textContent );
        }

        $teardown = "";

        foreach ( $teardown_tags as $teardown_tag ) {
            $teardown .= trim( $teardown_tag->textContent );
        }

        $test_cases = [];

        foreach ( $test_case_tags as $test_case_tag ) {
            $content = trim( $test_case_tag->textContent );
            $attributes = getTagAttributes( $test_case_tag );

            if ( !isset( $attributes["name"] ) || !isset( $attributes["group"] ) ) {
                continue;
            }

            $name = $attributes["name"];
            $group = $attributes["group"];

            unset( $attributes["name"] );
            unset( $attributes["group"] );

            if ( isset( $attributes["covers"] ) ) {
                $covers = $attributes["covers"];
                unset( $attributes["covers"] );
            } else {
                $covers = null;
            }

            $test_cases[] = new TestCase( $name, $group, $wikipage->getTitle(), $attributes, $content, $covers );
        }

        return new self( $setup, $teardown, $test_cases, $wikipage->getTitle() );
    }

    /**
     * Creates a new TestClass object from the given Title by consulting the database. If the optional $names
     * array is given, it only includes the test cases specified by that array. It will throw an exception of
     * a name is given in the $names array that does not exist.
     *
     * @param Title $title
     * @param array $names
     * @return TestClass
     *
     * @throws Exception
     */
    public static function newFromDb( Title $title, array $names = [] ): TestClass {
        $dbr = wfGetDB( DB_REPLICA );

        $setup_db_result = $dbr->select(
            "mwunit_setup",
            [ "content" ],
            [ "article_id" => $title->getArticleID() ]
        );

        $teardown_db_result = $dbr->select(
            "mwunit_teardown",
            [ "content" ],
            [ "article_id" => $title->getArticleID() ]
        );

        $test_cases_db_result = $dbr->select(
            "mwunit_tests",
            [ "test_group", "test_name", "covers" ],
            [ "article_id" => $title->getArticleID() ]
        );

        $setup = $setup_db_result->numRows() > 0 ? $setup_db_result->current()->content : "";
        $teardown = $teardown_db_result->numRows() > 0 ? $teardown_db_result->current()->content : "";
        $test_cases = [];

        foreach( $test_cases_db_result as $test_case_db_result ) {
            $test_name = $test_case_db_result->test_name;

            if ( !in_array( $test_name, $names ) && $names !== [] ) {
                // This test name is not in $names; skip it
                continue;
            }

            $test_cases[] = TestCase::newFromName( $test_name, $title );
        }

        return new self( $setup, $teardown, $test_cases, $title );
    }

    /**
     * TestClass constructor.
     *
     * @param string $setup
     * @param string $teardown
     * @param array $test_cases
     * @param Title $title
     */
    public function __construct(string $setup, string $teardown, array $test_cases, Title $title ) {
        $this->setup = $setup;
        $this->teardown = $teardown;
        $this->test_cases = $test_cases;
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getSetUp(): string {
        return $this->setup;
    }

    /**
     * @return string
     */
    public function getTearDown(): string {
        return $this->teardown;
    }

    /**
     * @return TestCase[]
     */
    public function getTestCases(): array {
        return $this->test_cases;
    }

    /**
     * @return Title
     */
    public function getTitle(): Title {
        return $this->title;
    }
}