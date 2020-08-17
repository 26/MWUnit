<?php

namespace MWUnit;

use Title;

class TestCase {
    private $name;
    private $group;
    private $title;

    /**
     * @param $row
     * @return false|TestCase
     */
    public static function newFromRow( $row ) {
        if ( !$row->article_id ) {
            return false;
        }

        $title = \Title::newFromId( $row->article_id );

        if ( !$row->test_name ) {
            return false;
        }

        $name = $row->test_name;

        if ( !$row->test_group ) {
            return false;
        }

        $group = $row->test_group;

        return new TestCase( $name, $group, $title );
    }

    /**
     * TestCase constructor.
     * @param string $name
     * @param string $group
     * @param Title $title
     */
    public function __construct( string $name, string $group, Title $title ) {
        $this->name = $name;
        $this->group = $group;
        $this->title = $title;
    }

    /**
     * Returns the name of this test case.
     *
     * @return string The name of this test case
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Returns the group this test case is in.
     *
     * @return string The group this test case is in
     */
    public function getGroup(): string {
        return $this->group;
    }

    /**
     * Returns the Title object associated with this TestCase.
     *
     * @return Title The title object of the page this test case is on
     */
    public function getTitle(): Title {
        return $this->title;
    }

    /**
     * Converts the test case to a human readable name.
     *
     * @return string
     */
    public function __toString(): string {
        return $this->title->getText() . "::" . $this->name;
    }

    /**
     * Returns true if and only if the given TestCase object refers to the same test case as
     * this object.
     *
     * @param TestCase $test_case
     * @return bool
     */
    public function equals( TestCase $test_case ) {
        return $this->group === $test_case->getGroup() &&
            $this->name === $test_case->getName() &&
            $this->getTitle()->getArticleId() === $test_case->getTitle()->getArticleId();
    }
}