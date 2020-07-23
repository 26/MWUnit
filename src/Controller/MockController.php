<?php

namespace MWUnit\Controller;

use MWUnit\MWUnit;
use MWUnit\Registry\MockRegistry;
use MWUnit\TestCaseRun;
use Parser;
use PPFrame;
use Revision;
use Title;

class MockController {
	/**
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 * @return string
	 */
	public static function handleCreateMock( Parser $parser, PPFrame $frame, array $args ) {
		if ( !isset( $args[0] ) ) {
			return MWUnit::error( "mwunit-set-mock-missing-argument", [ "page title" ] );
		}

		if ( !isset( $args[1] ) ) {
			return MWUnit::error( "mwunit-set-mock-missing-argument", [ "mock content" ] );
		}

		$page = trim( $frame->expand( $args[0] ) );
		$mock_content = trim(
			$frame->expand(
				$args[1],
				PPFrame::NO_ARGS & PPFrame::NO_IGNORE & PPFrame::NO_TAGS & PPFrame::NO_TEMPLATES
			)
		);

		if ( strpos( $page, ":" ) === false ) {
			// Interpret page without namespace as template
			$title = Title::newFromText( $page, NS_TEMPLATE );
		} else {
			$title = Title::newFromText( $page );
		}

		if ( !$title instanceof Title || !$title->exists() ) {
			return MWUnit::error( "mwunit-set-mock-bad-title" );
		}

		$mock_registry = MockRegistry::getInstance();
		$mock_registry->registerMock( $title, $mock_content );

		return '';
	}

	/**
	 * Called when the parser fetches a template. Used to replace the template with
	 * a mock.
	 *
	 * @param Parser $parser
	 * @param Title $title
	 * @param Revision $revision
	 * @param string|false|null $text
	 * @param array $deps
	 */
	public static function onParserFetchTemplate(
		Parser $parser,
		Title $title,
		Revision $revision,
		&$text,
		array &$deps
	) {
		if ( !MWUnit::isRunning() ) {
			return;
		}

		$registry = MockRegistry::getInstance();
		if ( $registry->isMocked( $title ) ) {
			if ( $title->getNamespace() === NS_TEMPLATE &&
				strtolower( $title->getText() ) === strtolower( TestCaseRun::$covered ) ) {
				TestCaseRun::$test_result->setRisky( wfMessage( "mwunit-mocked-cover-template" ) );
				return;
			}

			$text = $registry->getMock( $title );
		}
	}
}