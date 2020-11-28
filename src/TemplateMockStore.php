<?php

namespace MWUnit;

use MWUnit\Exception\MWUnitException;
use Title;

class TemplateMockStore {
	protected static $instance = null;

	/**
	 * Key value pair, where the key is the page ID for the page that is mocked and
	 * the value is the mock value.
	 *
	 * @var string[]
	 */
	private $mocks = [];

	private function __construct() {
	}

	/**
	 * Returns the instance of TemplateMockRegistry.
	 *
	 * @return TemplateMockStore
	 */
	public static function getInstance(): TemplateMockStore {
		if ( !isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns true if and only if the given Title is mocked.
	 *
	 * @param Title $title
	 * @return bool
	 */
	public function exists( Title $title ): bool {
		return isset( $this->mocks[ $title->getArticleID() ] );
	}

	/**
	 * Returns the value of the given mock or null if the given
	 * page is not mocked.
	 *
	 * @param Title $title
	 * @return string
	 * @throws MWUnitException
	 */
	public function get( Title $title ) {
		if ( !$this->exists( $title ) ) {
			throw new MWUnitException( "mwunit-exception-invalid-mock", [ $title->getFullText() ] );
		}

		return $this->mocks[ $title->getArticleID() ];
	}

	/**
	 * Registers a new mock or overwrites an existing mock.
	 *
	 * @param Title $title
	 * @param string $content
	 */
	public function register( Title $title, string $content ) {
		$id = $title->getArticleID();
		$this->mocks[ $id ] = $content;

		MWUnit::getLogger()->notice( "Registering mock for {title}", [
			"title" => $title->getFullText()
		] );
	}

	/**
	 * Resets the TemplateMockRegistry.
	 */
	public function reset() {
		$this->mocks = [];
	}
}
