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
		ParserFunctionFactory::newFromParser( $parser )->registerFunctionHandlers();
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

		$tables = [
		    "mwunit_tests"      => sprintf( "%s/%s/table_mwunit_tests.sql", $directory, $type ),
            "mwunit_teardown"   => sprintf( "%s/%s/table_mwunit_teardown.sql", $directory, $type ),
            "mwunit_setup"      => sprintf( "%s/%s/table_mwunit_setup.sql", $directory, $type ),
            "mwunit_content"    => sprintf( "%s/%s/table_mwunit_content.sql", $directory, $type ),
            "mwunit_attributes" => sprintf( "%s/%s/table_mwunit_attributes.sql", $directory, $type )
        ];

		foreach ( $tables as $table ) {
            if ( !file_exists( $table ) ) {
                throw new MWException( wfMessage( 'mwunit-invalid-dbms', $type )->parse() );
            }
        }

		foreach ( $tables as $table_name => $sql_path ) {
            $updater->addExtensionTable( $table_name, $sql_path );
        }
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
	    if ( $out->getTitle()->getNamespace() < 0 ) {
	        return;
        }

	    if ( $out->getWikiPage()->getContentModel() !== CONTENT_MODEL_TEST ) {
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
	    $title = $skin->getTitle();
	    $namespace = $title->getNamespace();

		if ( $namespace === NS_TEMPLATE && self::isTemplateCovered( $title ) ) {
			$special_title = Title::newFromText( 'UnitTests', NS_SPECIAL );
			$sidebar[ wfMessage( 'mwunit-sidebar-header' )->parse() ] = [
				[
					'text' => wfMessage( 'mwunit-sidebar-run-tests-for-template' )->parse(),
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

		$special_title = Title::newFromText( 'Special:UnitTests' );
		$sidebar[ wfMessage( 'mwunit-sidebar-header' )->parse() ] = [
			[
				'text' => wfMessage( 'mwunit-sidebar-run-tests' )->parse(),
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
     * Specify whether a page can be moved for technical reasons.
     *
     * @param Title $old
     * @param Title $new
     * @param \Status $status
     */
    public static function onMovePageIsValidMove( Title $old, Title $new, \Status &$status ) {
        $new_namespace = $new->getNamespace();
        $new_content_model = $new->getContentModel();

        if ( $new_namespace === NS_TEST && $new_content_model === CONTENT_MODEL_TEST ) {
            return;
        }

        if ( $new_namespace !== NS_TEST && $new_content_model !== CONTENT_MODEL_TEST) {
            return;
        }

        $status->fatal( "mwunit-cannot-move" );
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
    public static function testNameToSentence( string $test_name ) {
        $parts = preg_split( '/(?=[A-Z_\-])/', $test_name, -1, PREG_SPLIT_NO_EMPTY );
        $parts = array_map( function ( $part ): string {
            return ucfirst( trim( $part, '_- ' ) );
        }, $parts );
        $parts = array_filter( $parts, function( $part ): bool {
            return !empty( $part );
        } );

        if ( count( $parts ) < 1 ) {
            return "";
        }

        if ( count( $parts ) !== 1 && $parts[0] === "Test" ) {
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
     * Returns true if and only if the given Title object exists, is a template and has tests written for it.
     *
     * @param Title $title
     * @return bool
     */
    public static function isTemplateCovered( Title $title ): bool {
        if ( !$title->exists() ) {
            return false;
        }

        if ( $title->getNamespace() !== NS_TEMPLATE ) {
            return false;
        }

        $template_name = $title->getText();

        return wfGetDb( DB_REPLICA )->select(
                'mwunit_tests',
                [ 'article_id' ],
                [ 'covers' => $template_name ],
                __METHOD__
            )->numRows() > 0;
    }
}
