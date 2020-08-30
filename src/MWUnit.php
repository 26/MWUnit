<?php

namespace MWUnit;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MWException;
use MWUnit\Factory\ParserFunctionFactory;
use MWUnit\Factory\TagFactory;
use Parser;
use Psr\Log\LoggerInterface;
use Title;

abstract class MWUnit {
	const LOGGING_CHANNEL = "MWUnit"; // phpcs:ignore

	/**
	 * Called when the parser initializes for the first time.
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$tag_factory                = TagFactory::newFromParser( $parser );
		$parser_function_factory    = ParserFunctionFactory::newFromParser( $parser );

        $tag_factory->registerFunctionHandlers();
		$parser_function_factory->registerFunctionHandlers();
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
     * Allows last minute changes to the output page, e.g. adding of CSS or JavaScript by extensions.
     *
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
     *
     * @param \OutputPage $out
     * @param \Skin $skin
     */
	public static function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {
	    if ( $out->getTitle()->getNamespace() !== NS_TEST ) {
	        return;
        }

	    $out->addModuleStyles( [ "ext.mwunit.TestContent.css" ] );
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
            TestCaseRepository::getInstance()->isTemplateCovered( $skin->getTitle() ) ) {
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
     * Allows extensions to extend core's PHPUnit test suite.
     *
     * @param array $paths
     * @return bool
     */
	public static function onUnitTestsList( array &$paths ) {
        $paths[] = __DIR__ . '/../tests/phpunit/';
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

    /**
     * Called right after MediaWiki processes MWUnit's extension.json file.
     */
    public static function registrationCallback() {
        define( "CONTENT_MODEL_TEST", "test" );
        define( "CONTENT_FORMAT_TEST", "text/x-wiki-test" );
    }

    /**
     * @param Title $title
     * @param $model
     * @return bool
     */
    public static function onContentHandlerDefaultModelFor( Title $title, &$model ) {
        if ( $title->getNamespace() === NS_TEST ) {
            $model = CONTENT_MODEL_TEST;
            return false;
        }

        return true;
    }

    /**
     * Checks if the given attributes are valid, and return true if and only if all given
     * attributes are valid. It fills the second parameter with an array of errors.
     *
     * @param array $tag_arguments
     * @param array &$errors
     * @return bool
     * @throws \ConfigException
     */
    public static function areAttributesValid( array $tag_arguments, array &$errors = [] ): bool {
        $errors = [];

        if ( !isset( $tag_arguments[ 'name' ] ) ) {
            // The "name" argument is required.
            $errors[] = wfMessage( 'mwunit-missing-test-name' )->plain();
        } else if ( strlen( $tag_arguments['name'] ) > 255 || preg_match( '/^[A-Za-z0-9_\-]+$/', $tag_arguments['name'] ) !== 1 ) {
            $errors[] = wfMessage( 'mwunit-invalid-test-name', $tag_arguments['name'] )->plain();
        }

        if ( !isset( $tag_arguments[ 'group' ] ) ) {
            // The "group" argument is required.
            $errors[] = wfMessage( 'mwunit-missing-group' )->plain();
        } else if ( strlen( $tag_arguments['group'] ) > 255 || preg_match( '/^[A-Za-z0-9_\- ]+$/', $tag_arguments['group'] ) !== 1 ) {
            $errors[] = wfMessage( 'mwunit-invalid-group-name', $tag_arguments['group'] )->plain();
        }

        $force_covers = MediaWikiServices::getInstance()
            ->getMainConfig()
            ->get( 'MWUnitForceCoversAnnotation' );

        if ( $force_covers && !isset( $tag_arguments[ 'covers' ] ) ) {
            $errors[] = wfMessage( 'mwunit-missing-covers-annotation', $name )->plain();
        }

        return count( $errors ) === 0;
    }
}
