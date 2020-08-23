<?php

namespace MWUnit;

use ConfigException;
use MediaWiki\MediaWikiServices;
use Parser;
use Title;

/**
 * Class ConcreteTestCase
 *
 * @package MWUnit
 */
class ConcreteTestCase extends TestCase {
	private $input;
	private $options;

	/**
	 * ConcreteTestCase constructor.
	 *
	 * @param string $input The contents of the test case
	 * @param string $name The name of this test case
	 * @param string $group The group this test case is in
	 * @param array $options Associative array of additional options
	 * @param Title $title The Title object for this test case
	 */
	public function __construct(
		string $input,
		string $name,
		string $group,
		array $options,
		Title $title ) {
	    parent::__construct( $name, $group, $title );
        $this->input = $input;
        $this->options = $options;
	}

	/**
	 * Creates a new TestCase object from input received by tag register callback
	 *
	 * @param string $tag_input The input given directly to the tag
	 * @param array $tag_arguments The arguments given to the tag, entered like HTML tag attributes
	 * @param Parser $parser The parent parser
	 * @return ConcreteTestCase The newly created TestCase object
	 * @throws Exception\TestCaseException Thrown whenever some required argument is missing from $arguments.
     * @throws ConfigException
	 */
	public static function newFromTag( string $tag_input, array $tag_arguments, Parser $parser ) {
		if ( !isset( $tag_arguments[ 'name' ] ) ) {
            // The "name" argument is required.
            throw new Exception\TestCaseException( 'mwunit-missing-test-name' );
		}

        $name = self::array_shift_key( 'name', $tag_arguments );

		if ( strlen( $name ) > 255 || preg_match( '/^[A-Za-z0-9_\-]+$/', $name ) !== 1 ) {
			throw new Exception\TestCaseException( 'mwunit-invalid-test-name', [ $name ] );
		}

		if ( !isset( $tag_arguments[ 'group' ] ) ) {
            // The "group" argument is required.
            throw new Exception\TestCaseException( 'mwunit-missing-group' );
		}

        $group = self::array_shift_key( 'group', $tag_arguments );

		if ( strlen( $group ) > 255 || preg_match( '/^[A-Za-z0-9_\- ]+$/', $group ) !== 1 ) {
			throw new Exception\TestCaseException( 'mwunit-invalid-group-name', [ $group ] );
		}

		$force_covers = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( 'MWUnitForceCoversAnnotation' );

		if ( $force_covers && !isset( $tag_arguments[ 'covers' ] ) ) {
			throw new Exception\TestCaseException( 'mwunit-missing-covers-annotation', [ $name ] );
		}

		$title = $parser->getTitle();

		return new ConcreteTestCase( $tag_input, $name, $group, $tag_arguments, $title );
	}

	/**
	 * Returns the contents of this test case.
	 *
	 * @return string The contents of the test case
	 */
	public function getInput(): string {
		return $this->input;
	}

	/**
	 * Returns the option with the given name if it is set, else it returns false.
	 *
	 * @param string $option
	 * @return bool|string
	 */
	public function getOption( string $option ) {
		return isset( $this->options[ $option ] ) ? $this->options[ $option ] : false;
	}

	/**
	 * Returns an associative array of options.
	 *
	 * @return array The options as a key-value pair
	 */
	public function getOptions(): array {
		return $this->options;
	}

    /**
     * Removes the element in the given array specified by the given key and returns it.
     *
     * @param $key
     * @param $array
     * @return mixed
     */
	private static function array_shift_key( $key, &$array ) {
	    $value = $array[$key];
	    unset( $array[$key] );

	    return $value;
    }
}
