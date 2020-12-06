<?php

namespace MWUnit\ParserFunction;

use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use MWUnit\ParserData;
use MWUnit\Runner\TestRun;
use MWUnit\TemplateMockStore;
use MWUnit\TestRunInjector;
use Parser;
use PPFrame;
use Revision;
use Title;

/**
 * Class TemplateMockController
 * @package MWUnit\Controller
 */
class TemplateMockParserFunction implements ParserFunction, TestRunInjector {
	/**
	 * @var TestRun
	 */
	private static $run;

	/**
	 * @inheritDoc
	 */
	public static function setTestRun( TestRun $run ) {
		self::$run = $run;
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
	 * @throws MWUnitException
	 */
	public static function onParserFetchTemplate(
		Parser $parser,
		Title $title,
		Revision $revision,
		&$text,
		array &$deps
	) {
		$registry = TemplateMockStore::getInstance();

		if ( !$registry->exists( $title ) ) {
			return;
		}

		if ( !self::$run ) {
			return;
		}

		if ( $title->getNamespace() === NS_TEMPLATE &&
			strtolower( $title->getText() ) === strtolower( self::$run->getTestCase()->getCovers() ) ) {
			self::$run->setRisky( wfMessage( "mwunit-mocked-cover-template" )->parse() );
			return;
		}

		$text = $registry->get( $title );
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

		// Interpret page title without namespace prefix as a template.
		$title = strpos( $page, ":" ) === false ?
			Title::newFromText( $page, NS_TEMPLATE ) :
			Title::newFromText( $page );

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

		$mock_registry = TemplateMockStore::getInstance();
		$mock_registry->register( $title, $content );

		return '';
	}
}
