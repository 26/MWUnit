<?php

namespace MWUnit;

use Exception;
use Title;

class TestCase {
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $group;

    /**
     * @var Title
     */
    private $title;

    /**
     * @var string[]
     */
    private $attributes;

    /**
     * @var string
     */
    private $content;

    /**
     * @var Title|null
     */
    private $covers;

    /**
     * Creates a new TestCase object from the given $name and $group or returns false if the test case does not
     * exist.
     *
     * @param string $name
     * @param Title $test_page
     *
     * @return TestCase|false
     * @throws Exception
     */
    public static function newFromName( string $name, Title $test_page ) {
        $dbr = wfGetDB( DB_REPLICA );

        $test_case_db_result = $dbr->select(
            "mwunit_tests",
            [ "test_group", "covers" ],
            [ "article_id" => $test_page->getArticleID(), "test_name" => $name ]
        );

        if ( $test_case_db_result->numRows() < 1 ) {
            return false;
        }

        $test_group = $test_case_db_result->current()->test_group;
        $covers = $test_case_db_result->current()->covers ?: null;

        $attributes_db_result = $dbr->select(
            "mwunit_attributes",
            [ "attribute_name", "attribute_value" ],
            [ "article_id" => $test_page->getArticleID(), "test_name" => $name ]
        );

        $attributes = [];

        foreach( $attributes_db_result as $attribute_db_result ) {
            $attributes[$attribute_db_result->attribute_name] = $attribute_db_result->attribute_value;
        }

        $content_db_result = $dbr->select(
            "mwunit_content",
            [ "content" ],
            [ "article_id" => $test_page->getArticleID(), "test_name" => $name ]
        );

        if ( $content_db_result->numRows() < 1 ) {
            throw new Exception( "Missing content for test case $name" );
        }

        $content = $content_db_result->current()->content;

        return new self( $name, $test_group, $test_page, $attributes, $content, $covers );
    }

    /**
     * TestCase constructor.
     *
     * @param string $name
     * @param string $group
     * @param Title $test_page
     * @param array $attributes
     * @param string $content
     * @param Title|null $covers
     */
    public function __construct( string $name, string $group, Title $test_page, array $attributes, string $content, $covers = null ) {
        $this->name = $name;
        $this->group = $group;
        $this->title = $test_page;
        $this->attributes = $attributes;
        $this->content = $content;
        $this->covers = $covers;
    }

    /**
     * @return string
     */
    public function getTestName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTestGroup(): string {
        return $this->group;
    }

    /**
     * @return Title
     */
    public function getTestPage(): Title {
        return $this->title;
    }

    /**
     * @return array|string[]
     */
    public function getAttributes(): array {
        return $this->attributes;
    }

    /**
     * Returns the value of a specific attribute, or false if it does not exist.
     *
     * @param string $attribute
     * @return string
     */
    public function getAttribute( string $attribute ): string {
        return $this->attributes[$attribute] ?? false;
    }

    /**
     * @return string
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * @return Title|null
     */
    public function getCovers() {
        return $this->covers;
    }

    /**
     * Returns the canonical name of this test case.
     *
     * @return string
     */
    public function getCanonicalName(): string {
        return $this->title->getText() . "::" . $this->name;
    }

    /**
     * Outputs the string representation of this object.
     *
     * @return string
     */
    public function __toString() {
        return $this->getCanonicalName();
    }
}