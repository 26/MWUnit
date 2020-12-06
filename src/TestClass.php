<?php

namespace MWUnit;

use DOMNode;
use MediaWiki\MediaWikiServices;
use MWUnit\Exception\InvalidTestPageException;
use MWUnit\Exception\MWUnitException;
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
	 * Creates a new TestClass object from the given wikitext. Throws an InvalidTestPageException if the
	 * wikitext is invalid.
	 *
	 * @param string $wikitext
	 * @param Title $title
	 * @return TestClass
	 * @throws InvalidTestPageException
	 */
	public static function newFromWikitext( string $wikitext, Title $title ): TestClass {
		$errors = [];

		$parser = new TestPageParser();
		$tag_sets = $parser->parse( $wikitext );

		$setup_tags = $tag_sets["setup"] ?? [];
		$teardown_tags = $tag_sets["teardown"] ?? [];
		$test_case_tags = $tag_sets["testcase"] ?? [];

		try {
			$max_test_cases = MediaWikiServices::getInstance()->getMainConfig()->get( "MWUnitMaxTestCases" );
		} catch ( \ConfigException $e ) {
			$max_test_cases = 2048;
		}

		$actual_test_cases = count( $test_case_tags );

		if ( $actual_test_cases > $max_test_cases ) {
			// This page exceeded the maximum number of test cases
			throw new InvalidTestPageException( [ wfMessage( "mwunit-max-test-cases-exceeded", $max_test_cases, $actual_test_cases ) ] );
		}

		$setup = "";

		if ( count( $setup_tags ) > 1 ) {
			$errors[] = wfMessage( "mwunit-duplicate-setup" );
		} elseif ( isset( $setup_tags[0] ) ) {
			$setup = $setup_tags[0]->textContent;
		}

		$teardown = "";

		if ( count( $teardown_tags ) > 1 ) {
			$errors[] = wfMessage( "mwunit-duplicate-teardown" );
		} elseif ( isset( $teardown_tags[0] ) ) {
			$teardown = $teardown_tags[0]->textContent;
		}

		$test_cases = [];
		$test_names = [];

		foreach ( $test_case_tags as $idx => $test_case_tag ) {
			$content = trim( $test_case_tag->textContent );
			$attributes = self::getTagAttributes( $test_case_tag );

			// Set the correct suffix for use in error messages
			switch ( $idx + 1 ) {
				case 1:
					$suffix = "st";
					break;
				case 2:
					$suffix = "nd";
					break;
				case 3:
					$suffix = "rd";
					break;
				default:
					$suffix = "th";
					break;
			}

			if ( !isset( $attributes["name"] ) ) {
				$errors[] = wfMessage( "mwunit-missing-annotation", "name", $idx + 1, $suffix );
				continue;
			}

			if ( !isset( $attributes["group"] ) ) {
				$errors[] = wfMessage( "mwunit-missing-annotation", "group", $idx + 1, $suffix );
				continue;
			}

			$name = $attributes["name"];
			$group = $attributes["group"];

			if ( in_array( $name, $test_names ) ) {
				$errors[] = wfMessage( "mwunit-duplicate-entry", $name );
				continue;
			}

			unset( $attributes["name"] );
			unset( $attributes["group"] );

			try {
				$force_covers = MediaWikiServices::getInstance()->getMainConfig()->get( "MWUnitForceCoversAnnotation" );
			} catch ( \ConfigException $e ) {
				$force_covers = false;
			}

			if ( isset( $attributes["covers"] ) ) {
				$covers = $attributes["covers"];
				unset( $attributes["covers"] );
			} elseif ( $force_covers ) {
				$errors[] = wfMessage( "mwunit-missing-covers", $name );
				continue;
			} else {
				$covers = null;
			}

			$test_cases[] = new TestCase( $name, $group, $title, $attributes, $content, $covers );
			$test_names[] = $name;
		}

		if ( count( $errors ) > 0 ) {
			throw new InvalidTestPageException( $errors );
		}

		return new self( $setup, $teardown, $test_cases, $title );
	}

	/**
	 * Returns the KV-list of attributes for the given DOMNode.
	 *
	 * @param DOMNode $dom_node
	 * @return array
	 */
	private static function getTagAttributes( DOMNode $dom_node ): array {
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

	/**
	 * Creates a new TestClass object from the given Title by consulting the database. If the optional $names
	 * array is given, it only includes the test cases specified by that array. It will throw an exception of
	 * a name is given in the $names array that does not exist.
	 *
	 * @param Title $title
	 * @param array $names
	 * @return TestClass
	 *
	 * @throws MWUnitException
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

		foreach ( $test_cases_db_result as $test_case_db_result ) {
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
	public function __construct( string $setup, string $teardown, array $test_cases, Title $title ) {
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

	/**
	 * Updates/stores this instance in the database.
	 */
	public function doUpdate() {
		$dbr = wfGetDB( DB_MASTER );
		$article_id = $this->title->getArticleID();

		// Delete original setup from the database
		$dbr->delete(
			"mwunit_setup",
			[ "article_id" => $article_id ]
		);

		// Insert setup function
		$dbr->insert(
			"mwunit_setup",
			[ "article_id" => $article_id, "content" => $this->setup ]
		);

		// Delete original teardown from the database
		$dbr->delete(
			"mwunit_teardown",
			[ "article_id" => $article_id ]
		);

		// Insert teardown function
		$dbr->insert(
			"mwunit_teardown",
			[ "article_id" => $article_id, "content" => $this->teardown ]
		);

		foreach ( $this->test_cases as $test_case ) {
			$article_id = $test_case->getTestPage()->getArticleID();
			$test_name = $test_case->getTestName();

			$dbr->delete(
				"mwunit_tests",
				[ "article_id" => $article_id, "test_name" => $test_name ]
			);

			$dbr->delete(
				"mwunit_content",
				[ "article_id" => $article_id, "test_name" => $test_name ]
			);

			$dbr->delete(
				"mwunit_attributes",
				[ "article_id" => $article_id, "test_name" => $test_name ]
			);

			$dbr->insert(
				"mwunit_tests",
				[
					"article_id" => $article_id,
					"test_name" => $test_name,
					"test_group" => $test_case->getTestGroup(),
					"covers" => $test_case->getCovers() ?? ""
				]
			);

			$dbr->insert(
				"mwunit_content",
				[
					"article_id" => $article_id,
					"test_name" => $test_name,
					"content" => $test_case->getContent()
				]
			);

			foreach ( $test_case->getAttributes() as $attribute_name => $attribute_value ) {
				$dbr->insert(
					"mwunit_attributes",
					[
						"article_id" => $article_id,
						"test_name" => $test_name,
						"attribute_name" => $attribute_name,
						"attribute_value" => $attribute_value
					]
				);
			}
		}
	}
}
