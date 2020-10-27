<?php

namespace MWUnit;

use MediaWiki\MediaWikiServices;
use Title;

class DatabaseTestCase {
    private $name;
    private $group;
    private $title;
    private $covers;

    /**
     * @param $row
     * @return false|DatabaseTestCase
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
        $covers = $row->covers;

        return new DatabaseTestCase( $name, $group, $covers, $title );
    }

    /**
     * DatabaseTestCase constructor.
     * @param string $name
     * @param string $group
     * @param string|null $covers
     * @param Title $title
     */
    public function __construct( string $name, string $group, $covers, Title $title ) {
        $this->name = $name;
        $this->group = $group;
        $this->title = $title;
        $this->covers = $covers;
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
     * Returns the Title object associated with this DatabaseTestCase.
     *
     * @return Title The title object of the page this test case is on
     */
    public function getTitle(): Title {
        return $this->title;
    }

    /**
     * Returns the covers annotation for this test case, or null when not available.
     *
     * @return string|null
     */
    public function getCovers() {
        return $this->covers;
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
     * Returns true if and only if the given DatabaseTestCase object refers to the same test case as
     * this object.
     *
     * @param DatabaseTestCase $test_case
     * @return bool
     */
    public function equals(DatabaseTestCase $test_case ) {
        return $this->group === $test_case->getGroup() &&
            $this->name === $test_case->getName() &&
            $this->getTitle()->getArticleId() === $test_case->getTitle()->getArticleId();
    }
}