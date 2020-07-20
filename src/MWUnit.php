<?php

namespace MWUnit;

use MWException;
use MWUnit\Exception\MWUnitException;
use Parser;

class MWUnit {
	/**
	 * @var bool
	 */
	private static $testRunning = false;

	/**
	 * @param Parser $parser
	 * @throws MWException
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'testcase', [ TestCaseHandler::class, 'handleTestCase' ] );

		$parser->setFunctionHook(
			'assert_string_contains',
			[
				Assertion\StringContains::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_string_contains_ignore_case',
			[
				Assertion\StringContainsIgnoreCase::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_has_length',
			[
				Assertion\HasLength::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_empty',
			[
				Assertion\IsEmpty::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_not_empty',
			[
				Assertion\NotEmpty::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_equals',
			[
				Assertion\Equals::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_equals_ignore_case',
			[
				Assertion\EqualsIgnoreCase::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_page_exists',
			[
				Assertion\PageExists::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_greater_than',
			[
				Assertion\GreaterThan::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_greater_than_or_equal',
			[
				Assertion\GreaterThanOrEqual::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_is_integer',
			[
				Assertion\IsInteger::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_is_numeric',
			[
				Assertion\IsNumeric::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_less_than',
			[
				Assertion\LessThan::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_less_than_or_equal',
			[
				Assertion\LessThanOrEqual::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_string_ends_with',
			[
				Assertion\StringEndsWith::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_string_starts_with',
			[
				Assertion\StringStartsWith::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_error',
			[
				Assertion\Error::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_no_error',
			[
				Assertion\NoError::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'assert_that',
			[
				Assertion\That::class, 'assert'
			],
			Parser::SFH_OBJECT_ARGS
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

		$sql_file = sprintf( "%s/%s/table_tests.sql", $directory, $type );

		if ( !file_exists( $sql_file ) ) {
			throw new MWException( wfMessage( 'mwunit-invalid-dbms', $type )->plain() );
		}

		$updater->addExtensionTable( 'tests', $sql_file );
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
		self::$testRunning = true;
	}

	/**
	 * Returns true if and only if a test is currently running.
	 *
	 * @return bool
	 */
	public static function isRunning(): bool {
		return self::$testRunning === true;
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
}
