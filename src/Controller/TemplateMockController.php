<?php

namespace MWUnit\Controller;

use MWUnit\Exception\MWUnitException;
use MWUnit\Injector\TestRunInjector;
use MWUnit\Mock\Mock;
use MWUnit\MWUnit;
use MWUnit\Registry\TemplateMockRegistry;
use MWUnit\Runner\TestRun;
use Parser;
use PPFrame;
use Revision;
use Title;

/**
 * Class TemplateMockController
 * @package MWUnit\Controller
 */
class TemplateMockController implements TestRunInjector {
    /**
     * @var TestRun
     */
    private static $run;

    /**
     * @inheritDoc
     */
    public static function setTestRun(TestRun $run) {
        self::$run = $run;
    }

    /**
	 * Hooked to the #create_mock parser function.
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 * @return string
	 */
	public static function handleCreateMock( Parser $parser, PPFrame $frame, array $args ) {
		if ( !isset( $args[0] ) ) {
		    return MWUnit::error(
                "mwunit-create-mock-missing-argument",
                [ "1st (page title)" ]
            );
		}

		if ( !isset( $args[1] ) ) {
		    return MWUnit::error(
                "mwunit-create-mock-missing-argument",
                [ "2nd (mock content)" ]
            );
		}

		$page = trim( $frame->expand( $args[0] ) );
		$content = trim(
			$frame->expand(
				$args[1],
				PPFrame::NO_ARGS | PPFrame::NO_IGNORE | PPFrame::NO_TAGS | PPFrame::NO_TEMPLATES
			)
		);
		$mock = new Mock( $content );

		// Interpret page title without namespace prefix as a template.
		$title = strpos( $page, ":" ) === false ?
			Title::newFromText( $page, NS_TEMPLATE ) :
			Title::newFromText( $page );

		if ( !$title instanceof Title || !$title->exists() ) {
			return MWUnit::error( "mwunit-create-mock-bad-title" );
		}

		if ( $parser->getTitle()->getNamespace() !== NS_TEST ) {
			return MWUnit::error( "mwunit-outside-test-namespace" );
		}

		if ( !MWUnit::isRunning() ) {
		    return '';
		}

		$mock_registry = TemplateMockRegistry::getInstance();
		$mock_registry->registerMock( $title, $mock );

		return '';
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
		if ( !MWUnit::isRunning() ) {
		    return;
		}

		$registry = TemplateMockRegistry::getInstance();

		if ( !$registry->isMocked( $title ) ) {
		    return;
		}

		if ( $title->getNamespace() === NS_TEMPLATE &&
            strtolower( $title->getText() ) === strtolower( self::$run->getCovered() ) ) {
			self::$run->setRisky( wfMessage( "mwunit-mocked-cover-template" )->plain() );
			return;
		}

		$text = $registry->getMock( $title )->getMock();
	}
}
