<?php

namespace MWUnit\ContentHandler;

use MediaWiki\MediaWikiServices;
use MWUnit\Exception\InvalidTestPageException;
use MWUnit\MWUnit;
use MWUnit\Renderer\Document;
use MWUnit\Renderer\Tag;
use MWUnit\TestCase;
use MWUnit\TestClass;
use ParserOptions;
use ParserOutput;
use Title;

/**
 * Class AbstractTestContent
 *
 * @package MWUnit\ContentHandler
 */
class TestContent extends \AbstractContent {
	private $text;

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
	public function __construct( $text, $model_id = CONTENT_MODEL_TEST ) {
		parent::__construct( $model_id );
		$this->text = $text;
	}

	/**
	 * @inheritDoc
	 */
	public function getTextForSearchIndex() {
		return $this->text;
	}

	/**
	 * @inheritDoc
	 */
	public function getWikitextForTransclusion() {
		$allow_transclusion = MediaWikiServices::getInstance()->getMainConfig()->get( "MWUnitAllowTransclusion" );
		return $allow_transclusion ? $this->text : MWUnit::error( "mwunit-test-transclusion-error" );
	}

	/**
	 * @inheritDoc
	 */
	public function getNativeData() {
		return $this->text;
	}

	/**
	 * @inheritDoc
	 */
	public function getSize() {
		return strlen( $this->text );
	}

	/**
	 * @inheritDoc
	 */
	public function copy() {
		return self::newFromText( $this->text );
	}

	/**
	 * @inheritDoc
	 */
	public function isCountable( $hasLinks = null ) {
		return true;
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		return true;
	}

    /**
     * @inheritDoc
     */
    public function getText() {
        return $this->text;
    }

	/**
	 * @inheritDoc
	 */
	public function getTextForSummary( $max_length = 250 ) {
		$text = $this->getNativeData();
		$suffix = "";

		if ( strlen( $text ) > $max_length - 3 ) {
			$suffix = "...";
		}

		return mb_substr( $this->getNativeData(), 0, $max_length - 3 ) . $suffix;
	}

	/**
	 * @inheritDoc
	 */
	public function fillParserOutput(
		Title $title,
		$revId,
		ParserOptions $options,
		$generateHtml,
		ParserOutput &$output
	) {
		if ( !$generateHtml ) {
			return;
		}

		$this->fillOutputFromText( $output, $title, $this->text );
	}

	/**
	 * Fills the given ParserOutput object from the given $text.
	 *
	 * @param ParserOutput $output
	 * @param Title $title
	 * @param string $text
	 *
	 * @return void
	 */
	private function fillOutputFromText( ParserOutput $output, Title $title, string $text ) {
		$output->addModuleStyles( "ext.mwunit.TestPage.css" );

		try {
			$test_class = TestClass::newFromWikitext( $text, $title );
			$this->fillOutputFromTestClass( $output, $test_class );
		} catch ( InvalidTestPageException $e ) {
			$this->fillOutputFromException( $output, $e );
		}
	}

	private function fillOutputFromTestClass( ParserOutput $output, TestClass $test_class ) {
		$intro = new Tag(
			"p",
			wfMessage( "mwunit-testpage-intro" )->plain()
		);

		$header = new Tag(
			"div",
			new Tag(
				"div",
				wfMessage( "mwunit-tests" )->plain(),
				[ "class" => "mwunit-tab-label mwunit-regular-tab-label" ] ),
			[ "class" => "mwunit-table-header" ]
		);

		$table = $this->tableFromTestClass( $test_class );

		$container = new Tag(
			"div", new Document( [ $intro, $header, $table ] ), [ "class" => "mwunit-testpage" ]
		);

		$output->setText( $container->__toString() );
	}

	private function tableFromTestClass( TestClass $test_class ) {
		$setup = trim( $test_class->getSetUp() );
		$teardown = trim( $test_class->getTearDown() );
		$test_cases = $test_class->getTestCases();

		if ( !$setup && !$teardown && $test_cases === [] ) {
			return new Tag( "div", wfMessage( "mwunit-no-results" )->plain(), [ "class" => "mwunit-message-box" ] );
		}

		$table_items = [];

		if ( $setup ) {
			$table_items[] = new Tag(
				"div",
				new Tag(
					"div",
					$setup,
					[ "class" => "mwunit-test-content" ],
					true
				), [
					"title" => wfMessage( "mwunit-setup-method-hover" )->plain(),
					"class" => "mwunit-table-row mwunit-test-row mwunit-setup-row"
				]
			);
		}

		if ( $teardown ) {
			$table_items[] = new Tag(
				"div",
				new Tag(
					"div",
					$teardown,
					[ "class" => "mwunit-test-content" ],
					true
				), [
					"title" => wfMessage( "mwunit-teardown-method-hover" )->plain(),
					"class" => "mwunit-table-row mwunit-test-row mwunit-teardown-row"
				]
			);
		}

		foreach ( $test_cases as $test_case ) {
			$table_items[] = new Tag(
				"div", $this->htmlFromTestCase( $test_case ), [ "class" => "mwunit-table-row mwunit-test-row" ]
			);
		}

		return new Tag(
			"div",
			new Document( $table_items ),
			[ "class" => "mwunit-table" ]
		);
	}

	private function htmlFromTestCase( TestCase $test_case ): Tag {
		$text_parts = [];

		$test_content = $test_case->getContent();
		$test_name = $test_case->getCanonicalName();
		$group = $test_case->getTestGroup();
		$covers = $test_case->getCovers();

		$attributes = [];

		$attributes[] = "@group $group";

		foreach ( $test_case->getAttributes() as $key => $value ) {
			$attributes[] = "@$key $value";
		}

		if ( $covers ) {
			$attributes[] = "@covers $covers";
		}

		$text_parts[] = $test_name;

		if ( count( $attributes ) > 0 ) {
			$text_parts[] = implode( "\n", $attributes );
		}

		$text_parts[] = $test_content;

		$text = implode( "\n------\n", $text_parts );

		return new Tag( "div", $text, [ "class" => "mwunit-test-content" ], true );
	}

	private function fillOutputFromException( ParserOutput $output, InvalidTestPageException $e ) {
		$output->addModuleStyles( "ext.mwunit.InvalidTestPage.css" );

		$errors = $e->getErrors();

		$errors_count = count( $errors );
		$max_errors = MediaWikiServices::getInstance()->getMainConfig()->get( "MWUnitMaxReportedErrors" );

		if ( $errors_count > $max_errors ) {
			$errors = array_slice( $errors, 0, $max_errors );

			// Show a message that there are too many errors to display.
			$too_many_errors_message = new Tag(
				"div",
				wfMessage( "mwunit-too-many-errors", $max_errors, $errors_count )->plain(),
				[ "class" => "mwunit-error-box" ]
			);
		} else {
			$too_many_errors_message = null;
		}

		$table_items = [];

		foreach ( $errors as $error ) {
			$table_items[] = new Tag(
				"div", $error->plain(), [ "class" => "mwunit-table-row" ]
			);
		}

		$intro = new Tag(
			"p",
			wfMessage( "mwunit-error-intro" )->plain()
		);

		$table_header = new Tag(
			"div",
			new Tag(
				"div",
				wfMessage( "mwunit-errors" )->plain(),
				[ "class" => "mwunit-tab-label mwunit-error-tab-label" ] ),
			[ "class" => "mwunit-table-header" ]
		);

		$table = new Tag(
			"div",
			new Document( $table_items ),
			[ "class" => "mwunit-table" ]
		);

		$container = new Tag(
			"div", new Document( [ $intro, $too_many_errors_message, $table_header, $table ] ), [ "class" => "mwunit-errors" ]
		);

		$output->setText( $container->__toString() );
	}
}
