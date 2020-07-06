<?php

namespace MWUnit;

/**
 * Class TestCase
 *
 * @package MWUnit
 */
class TestCase {
	private $input;
	private $name;
	private $group;

	private $parser;
	private $frame;

	/**
	 * TestCase constructor.
	 *
	 * @param string $input The contents of the test case
	 * @param string $name The name of this test case
	 * @param string $group The group this test case is in
	 * @param array $options Associative array of additional options
	 * @param \Parser $parser The parent Parser object for this test case
	 * @param \PPFrame $frame The parent PPFrame object for this test case
	 */
	public function __construct(
		string $input,
		string $name,
		string $group,
		array $options,
		\Parser $parser,
		\PPFrame $frame ) {
		$this->input = $input;
		$this->name = $name;
		$this->group = $group;
		$this->options = $options;
		$this->parser = $parser;
		$this->frame = $frame;
	}

	/**
	 * Creates a new TestCase object from input received by tag register callback
	 *
	 * @param string $tag_input The input given directly to the tag
	 * @param array $tag_arguments The arguments given to the tag, entered like HTML tag attributes
	 * @param \Parser $parser The parent parser
	 * @param \PPFrame $frame The parent frame
	 * @return TestCase The newly created TestCase object
	 * @throws Exception\TestCaseException Thrown whenever some required argument is missing from $arguments.
	 */
	public static function newFromTag( string $tag_input, array $tag_arguments, \Parser $parser, \PPFrame $frame ) {
		if ( isset( $tag_arguments[ 'name' ] ) ) {
			$name = $tag_arguments[ 'name' ];
			unset( $tag_arguments[ 'name' ] );
		} else {
			// The "name" argument is required.
			throw new Exception\TestCaseException( 'mwunit-missing-test-name' );
		}

		if ( strlen( $name ) > 255 || preg_match( '/^[A-Za-z0-9_\-]+$/', $name ) !== 1 ) {
			throw new Exception\TestCaseException( 'mwunit-invalid-test-name', [ $name ] );
		}

		if ( isset( $tag_arguments[ 'group' ] ) ) {
			$group = $tag_arguments[ 'group' ];
			unset( $tag_arguments[ 'group' ] );
		} else {
			// The "group" argument is required.
			throw new Exception\TestCaseException( 'mwunit-missing-group' );
		}

		if ( strlen( $group ) > 255 || preg_match( '/^[A-Za-z0-9_\-]+$/', $group ) !== 1 ) {
			throw new Exception\TestCaseException( 'mwunit-invalid-group-name', [ $group ] );
		}

		return new TestCase( $tag_input, $name, $group, $tag_arguments, $parser, $frame );
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
	 * Returns the name of this test case.
	 *
	 * @return string The name of this test case
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Returns the group this test case is in.
	 *
	 * @return string The group this test case is in
	 */
	public function getGroup(): string {
		return $this->group;
	}

	/**
	 * Returns the parent Parser object for this test case.
	 *
	 * @return \Parser The parent Parser object for this test case
	 */
	public function getParser(): \Parser {
		return $this->parser;
	}

	/**
	 * Returns the parent PPFrame (frame) object for this test case.
	 *
	 * @return \PPFrame The parent PPFrame object for this test case
	 */
	public function getFrame(): \PPFrame {
		return $this->frame;
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
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}
}
