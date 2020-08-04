<?php

namespace MWUnit;

use MediaWiki\Logger\LoggerFactory;
use MWException;
use MWUnit\Exception\MWUnitException;
use MWUnit\Registry\AssertionRegistry;
use MWUnit\Registry\TestCaseRegistry;
use Parser;
use Psr\Log\LoggerInterface;
use Title;

class MWUnit {
	const LOGGING_CHANNEL = "MWUnit"; // phpcs:ignore

	/**
	 * @var bool
	 */
	private static $test_running = false;

	/**
	 * Called when the parser initializes for the first time.
	 *
	 * @param Parser $parser
	 * @throws MWException
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook(
			'testcase',
			[ Controller\TestCaseController::class, 'handleTestCase' ]
		);

		$assertion_registry = AssertionRegistry::getInstance();
		$assertion_registry->registerAssertionClasses();

		$parser->setFunctionHook(
			'create_mock',
			[ Controller\MockController::class, 'handleCreateMock' ],
			SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'create_parser_mock',
			[ Controller\ParserMockController::class, 'handleCreateMock' ],
			SFH_OBJECT_ARGS
		);
	}

	/**
	 * Called whenever schema updates are required. Updates the database schema.
	 *
	 * @param \DatabaseUpdater $updater
	 * @throws MWException
	 */
	public static function onLoadExtensionSchemaUpdates( \DatabaseUpdater $updater ) {
		$directory = $GLOBALS['wgExtensionDirectory'] . '/MWUnit/sql';
		$type = $updater->getDB()->getType();
		$mwunit_tests_sql = sprintf( "%s/%s/table_mwunit_tests.sql", $directory, $type );

		if ( !file_exists( $mwunit_tests_sql ) ) {
			throw new MWException( wfMessage( 'mwunit-invalid-dbms', $type )->plain() );
		}

		$updater->addExtensionTable( 'mwunit_tests', $mwunit_tests_sql );
	}

	/**
	 * Called at the end of Skin::buildSidebar(). Adds applicable links to the
	 * skin's sidebar.
	 *
	 * Links are for instance added on all test pages or on covered templates.
	 *
	 * @param \Skin $skin
	 * @param array &$sidebar
	 * @return bool
	 */
	public static function onSkinBuildSidebar( \Skin $skin, array &$sidebar ) {
		if ( $skin->getTitle()->getNamespace() === NS_TEMPLATE &&
			TestCaseRegistry::isTemplateCovered( $skin->getTitle() ) ) {
			$special_title = Title::newFromText( 'Special:MWUnit' );
			$sidebar[ wfMessage( 'mwunit-sidebar-header' )->plain() ] = [
				[
					'text' => wfMessage( 'mwunit-sidebar-run-tests-for-template' )->plain(),
					'href' => $special_title->getFullURL( [
						'unitTestCoverTemplate' => $skin->getTitle()->getText()
					] ),
					'id' => 'mwunit-sb-run',
					'active' => '',
					'accesskey' => 'a'
				]
			];

			return true;
		}

		if ( $skin->getTitle()->getNamespace() !== NS_TEST ) {
			return true;
		}

		$special_title = Title::newFromText( 'Special:MWUnit' );
		$sidebar[ wfMessage( 'mwunit-sidebar-header' )->plain() ] = [
			[
				'text' => wfMessage( 'mwunit-sidebar-run-tests' )->plain(),
				'href' => $special_title->getFullURL( [ 'unitTestPage' => $skin->getTitle()->getFullText() ] ),
				'id' => 'mwunit-sb-run',
				'active' => '',
				'accesskey' => 'a'
			]
		];

		return true;
	}

	/**
	 * Returns a formatted error message.
	 *
	 * @param string $message
	 * @param array $params
	 * @return string
	 */
	public static function error( string $message, array $params = [] ): string {
		return \Html::rawElement(
			'span', [ 'class' => 'error' ], wfMessage( $message, $params )->toString()
		);
	}

	/**
	 * Sets a flag to tell other parts of the extension MWUnit is currently executing tests.
	 */
	public static function setRunning() {
		self::$test_running = true;
	}

	/**
	 * Returns true if and only if a test is currently running.
	 *
	 * @return bool True if running, false otherwise
	 */
	public static function isRunning(): bool {
		return self::$test_running === true;
	}

	/**
	 * Returns the canonical test name for the given $article_id and $test_name.
	 *
	 * @param int $article_id
	 * @param string $test_name
	 * @return string
	 * @throws MWUnitException Thrown when an invalid article ID is given
	 */
	public static function getCanonicalTestName( int $article_id, string $test_name ): string {
		$title = Title::newFromID( $article_id );
		if ( !$title instanceof Title || !$title->exists() ) {
			throw new MWUnitException( 'mwunit-invalid-article' );
		}

		return $title->getText() . "::" . $test_name;
	}

	/**
	 * Returns the canonical name of the given TestCase, which is used for
	 * identifying tests.
	 *
	 * @param TestCase $testcase
	 * @return string The canonical test name
	 */
	public static function getCanonicalTestNameFromTestCase( TestCase $testcase ): string {
		return $testcase->getParser()->getTitle()->getText() . "::" . $testcase->getName();
	}

	/**
	 * Returns MWUnit's logger interface.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Structured_logging
	 *
	 * @return LoggerInterface The logger interface
	 */
	public static function getLogger(): LoggerInterface {
		return LoggerFactory::getInstance( self::LOGGING_CHANNEL );
	}

    /**
     * Formats the given test name, in either camel case or snake case, into a more
     * human-readable sentence.
     *
     * @param string $test_name The test name
     * @return string
     */
    public static function testNameToSentence(string $test_name ) {
        $parts = preg_split( '/(?=[A-Z_\-])/', $test_name, -1, PREG_SPLIT_NO_EMPTY );
        $parts = array_map( function ( $part ): string {
            return ucfirst( trim( $part, '_- ' ) );
        }, $parts );

        if ( $parts[0] === "Test" ) {
            unset( $parts[0] );
        }

        return implode( " ", $parts );
    }
}
