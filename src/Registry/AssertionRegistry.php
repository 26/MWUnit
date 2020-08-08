<?php

namespace MWUnit\Registry;

use MediaWiki\MediaWikiServices;
use MWUnit\Assertion\Assertion;
use MWUnit\Assertion\Equals;
use MWUnit\Assertion\EqualsIgnoreCase;
use MWUnit\Assertion\Error;
use MWUnit\Assertion\GreaterThan;
use MWUnit\Assertion\GreaterThanOrEqual;
use MWUnit\Assertion\HasLength;
use MWUnit\Assertion\IsEmpty;
use MWUnit\Assertion\IsInteger;
use MWUnit\Assertion\IsNumeric;
use MWUnit\Assertion\LessThan;
use MWUnit\Assertion\LessThanOrEqual;
use MWUnit\Assertion\NoError;
use MWUnit\Assertion\NotEmpty;
use MWUnit\Assertion\PageExists;
use MWUnit\Assertion\SemanticMediaWiki\HasProperty;
use MWUnit\Assertion\SemanticMediaWiki\PropertyHasValue;
use MWUnit\Assertion\StringContains;
use MWUnit\Assertion\StringContainsIgnoreCase;
use MWUnit\Assertion\StringEndsWith;
use MWUnit\Assertion\StringStartsWith;
use MWUnit\Assertion\That;
use MWUnit\Controller\AssertionController;
use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use Parser;
use PPFrame;

/**
 * Class AssertionRegistry
 *
 * @package MWUnit\Registry
 */
class AssertionRegistry extends AbstractRegistry {
    protected static $instance = null;

	/**
	 * The parser to which to register the assertions.
	 *
	 * @var Parser
	 */
	private $parser;
	private $classes;

	protected function __construct() {
		$classes = [
			Equals::class,
			EqualsIgnoreCase::class,
			Error::class,
			GreaterThan::class,
			GreaterThanOrEqual::class,
			HasLength::class,
			IsEmpty::class,
			IsInteger::class,
			IsNumeric::class,
			LessThan::class,
			LessThanOrEqual::class,
			NoError::class,
			NotEmpty::class,
			PageExists::class,
			StringContains::class,
			StringContainsIgnoreCase::class,
			StringEndsWith::class,
			StringStartsWith::class,
			That::class,
			HasProperty::class,
			PropertyHasValue::class
		];

		\Hooks::run( "MWUnitGetAssertionClasses", [ &$classes ] );

		$this->classes = $classes;
		$this->parser  = MediaWikiServices::getInstance()->getParser();

		parent::__construct();
	}

    /**
     * @inheritDoc
     * @return AssertionRegistry
     */
    public static function getInstance(): AbstractRegistry {
        self::setInstance();
        return self::$instance;
    }

    /**
     * @inheritDoc
     */
    protected static function setInstance() {
	    if ( !isset( self::$instance ) ) {
	        self::$instance = new self();
        }
    }

    /**
     * Registers the assertion classes to the parser object.
     */
	public function registerAssertionClasses() {
		$assertions = array_filter( $this->classes, function ( $class ): bool {
		    try {
                $reflection_class = new \ReflectionClass( $class );
            } catch ( \ReflectionException $e ) {
		        return false;
            }

			return $reflection_class->implementsInterface( Assertion::class );
		} );

		$registering_classes = array_filter( $assertions, function ( $class ): bool {
			return $class::shouldRegister();
		} );

		foreach ( $registering_classes as $assertion ) {
		    $this->registerAssertionClass( $assertion );
        }
	}

    /**
     * Registers the given assertion class to the parser.
     *
     * @param string $assertion The assertion class name
     * @return bool True on success, false on failure
     */
	public function registerAssertionClass( $assertion ) {
		try {
			$reflection_class = new \ReflectionClass( $assertion );
		} catch ( \ReflectionException $e ) {
			MWUnit::getLogger()->error( "Unable to register assertion because the class could not be reflected (does not exist): {e}", [
				$e
			] );

			return false;
		}

        if ( !$reflection_class->implementsInterface( Assertion::class ) ) {
            MWUnit::getLogger()->warning(
                "Unable to register assertion given by class {class} because it does not implement the Assertion interface",
                [ "class" => $assertion ]
            );

            return false;
        }

		MWUnit::getLogger()->notice( "Registering assertion {assertion}", [
			"assertion" => $assertion::getName()
		] );

		$callback_function = function ( Parser $parser, PPFrame $frame, array $args ) use ( $assertion ) {
			return AssertionController::handleAssertionParserHook(
				$parser,
				$frame,
				$args,
				$assertion
			);
		};

		$id = "assert_" . $assertion::getName();

		try {
            $this->parser->setFunctionHook( $id, $callback_function, Parser::SFH_OBJECT_ARGS );
        } catch( \MWException $e ) {
		    return false;
        }

		return true;
	}
}
