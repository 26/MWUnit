<?php

namespace MWUnit\ParserFunction;

use MWUnit\MWUnit;
use MWUnit\ParserData;
use MWUnit\Runner\TestRun;
use MWUnit\TemplateMockStore;
use MWUnit\TemplateMockStoreInjector;
use MWUnit\TestRunInjector;
use Parser;
use PPFrame;
use Revision;
use Title;

/**
 * Class TemplateMockController
 * @package MWUnit\Controller
 */
class TemplateMockParserFunction implements ParserFunction, TestRunInjector, TemplateMockStoreInjector {
	/**
	 * @var TestRun
	 */
	private static $run;

	/**
	 * @var TemplateMockStore
	 */
	private static $template_mock_store;

	/**
	 * @inheritDoc
	 */
	public static function setTestRun( TestRun $run ) {
		self::$run = $run;
	}

	/**
	 * @inheritDoc
	 */
	public static function setTemplateMockStore( TemplateMockStore $store ) {
		self::$template_mock_store = $store;
	}

	/**
	 * Called when the parser fetches a template. Used to replace the template with
	 * a mock.
	 *
	 * @param Parser $parser
	 * @param Title $title
	 * @param Revision $revision
	 * @param string|false|null &$text
	 * @param array &$deps
	 */
	public static function onParserFetchTemplate(
		$parser,
		Title $title,
		$revision,
		&$text,
		array &$deps
	) {
		if ( !self::$template_mock_store->exists( $title ) ) {
			return;
		}

		if ( !self::$run ) {
			return;
		}

		$title_lower = strtolower( $title->getText() );
		$covers_lower = strtolower( self::$run->getTestCase()->getCovers() );

		if ( $title->getNamespace() === NS_TEMPLATE && $title_lower === $covers_lower ) {
			self::$run->setRisky( wfMessage( "mwunit-mocked-cover-template" )->parse() );
			return;
		}

		$text = self::$template_mock_store->get( $title ) ?? "";
	}

	/**
	 * Hooked to the #create_mock parser function.
	 *
	 * @param ParserData $data
	 * @return string
	 */
	public function execute( ParserData $data ) {
		try {
			$page = $data->getArgument( 0 );
		} catch ( \OutOfBoundsException $e ) {
			return MWUnit::error(
				"mwunit-create-mock-missing-argument",
				[ "1st (page title)" ]
			);
		}

		$title = $this->getTitleFromPage( $page );

		if ( !$title instanceof Title || !$title->exists() ) {
			return MWUnit::error( "mwunit-create-mock-bad-title" );
		}

		try {
			$data->setFlags( PPFrame::NO_ARGS | PPFrame::NO_IGNORE | PPFrame::NO_TAGS | PPFrame::NO_TEMPLATES );
			$content = $data->getArgument( 1 );
		} catch ( \OutOfBoundsException $e ) {
			return MWUnit::error(
				"mwunit-create-mock-missing-argument",
				[ "2nd (mock content)" ]
			);
		}

		self::$template_mock_store->register( $title, $content );

		return '';
	}

	/**
	 * Returns a new Title object from the given page title. This function should return
	 * a Title object in the Template namespace when no explicit namespace is given.
	 *
	 * @param string $page_text
	 * @return Title
	 */
	public function getTitleFromPage( string $page_text ): Title {
		// Interpret page title without namespace prefix as a template.
		return strpos( $page_text, ":" ) === false ?
			Title::newFromText( $page_text, NS_TEMPLATE ) :
			Title::newFromText( $page_text );
	}
}
