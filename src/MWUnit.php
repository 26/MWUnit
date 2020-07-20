<?php

namespace MWUnit;

use MWException;
use MWUnit\Exception\MWUnitException;
use Parser;

class MWUnit {
	const GLOBAL_ASSERTIONS = [ // phpcs:ignore
		'string_contains' 				=> 'assertStringContains',
		'string_contains_ignore_case' 	=> 'assertStringContainsIgnoreCase',
		'has_length' 					=> 'assertHasLength',
		'empty'							=> 'assertEmpty',
		'not_empty' 					=> 'assertNotEmpty',
		'equals' 						=> 'assertEquals',
		'equals_ignore_case' 			=> 'assertEqualsIgnoreCase',
		'page_exists' 					=> 'assertPageExists',
		'greater_than' 					=> 'assertGreaterThan',
		'greater_than_or_equal' 		=> 'assertGreaterThanOrEqual',
		'is_integer' 					=> 'assertIsInteger',
		'is_numeric' 					=> 'assertIsNumeric',
		'less_than' 					=> 'assertLessThan',
		'less_than_or_equal' 			=> 'assertLessThanOrEqual',
		'string_ends_with' 				=> 'assertStringEndsWith',
		'string_starts_with' 			=> 'assertStringStartsWith',
		'error' 						=> 'assertError',
		'no_error' 						=> 'assertNoError',
		'that' 							=> 'assertThat'
	];

	const SEMANTIC_ASSERTIONS = [ // phpcs:ignore
		'has_property' 	  				=> 'assertHasProperty',
		'property_has_value' 			=> 'assertPropertyHasValue'
	];

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
		$parser->setHook( 'testcase', [ Controllers\TestCaseController::class, 'handleTestCase' ] );
		self::registerFunctions( $parser );
	}

	/**
	 * Handles the registration of the parser functions.
	 *
	 * @param Parser $parser
	 * @throws MWException
	 */
	public static function registerFunctions( Parser $parser ) {
		self::registerAssertions( $parser, self::GLOBAL_ASSERTIONS );

		if ( \ExtensionRegistry::getInstance()->isLoaded( 'SemanticMediaWiki' ) ) {
			self::registerAssertions( $parser, self::SEMANTIC_ASSERTIONS );
		}
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
	 * Called at the end of Skin::buildSidebar().
	 *
	 * @param \Skin $skin
	 * @param array &$sidebar
	 * @return bool
	 */
	public static function onSkinBuildSidebar( \Skin $skin, array &$sidebar ) {
		if ( $skin->getTitle()->getNamespace() === NS_TEMPLATE &&
			TestCaseRegister::isTemplateCovered( $skin->getTitle() ) ) {
			$special_title = \Title::newFromText( 'Special:MWUnit' );
			$sidebar[ wfMessage( 'mwunit-sidebar-header' )->plain() ] = [
				[
					'text' => wfMessage( 'mwunit-sidebar-run-tests-for-template' ),
					'href' => $special_title->getFullURL( [
						'unitTestCoverTemplate' => $skin->getTitle()->getText()
					] ),
					'id' => 'mwunit-sb-run',
					'active' => ''
				]
			];
			return true;
		}

		if ( $skin->getTitle()->getNamespace() !== NS_TEST ) {
			return true;
		}

		$special_title = \Title::newFromText( 'Special:MWUnit' );
		$sidebar[ wfMessage( 'mwunit-sidebar-header' )->plain() ] = [
			[
				'text' => wfMessage( 'mwunit-sidebar-run-tests' ),
				'href' => $special_title->getFullURL( [ 'unitTestPage' => $skin->getTitle()->getFullText() ] ),
				'id' => 'mwunit-sb-run',
				'active' => ''
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

	public static function setRunning() {
		self::$test_running = true;
	}

	/**
	 * Returns true if and only if a test is currently running.
	 *
	 * @return bool
	 */
	public static function isRunning(): bool {
		return self::$test_running === true;
	}

	/**
	 * @param int $article_id
	 * @param string $test_name
	 * @return string
	 * @throws MWUnitException
	 */
	public static function getCanonicalTestName( int $article_id, string $test_name ): string {
		$title = \Title::newFromID( $article_id );
		if ( $title === null || $title === false || !$title->exists() ) {
			throw new MWUnitException( 'mwunit-invalid-article' );
		}

		return $title->getText() . "::" . $test_name;
	}

	/**
	 * @param TestCase $testcase
	 * @return string
	 */
	public static function getCanonicalTestNameFromTestCase( TestCase $testcase ): string {
		return $testcase->getParser()->getTitle()->getText() . "::" . $testcase->getName();
	}

	/**
	 * Registers the given list of assertions to the given parser.
	 *
	 * @param Parser $parser
	 * @param array $assertions
	 * @throws MWException
	 */
	private static function registerAssertions( Parser $parser, array $assertions ) {
		foreach ( $assertions as $assertion => $function ) {
			$parser->setFunctionHook(
				"assert_$assertion",
				[ Controllers\AssertionController::class, $function ],
				Parser::SFH_OBJECT_ARGS
			);
		}
	}
}
