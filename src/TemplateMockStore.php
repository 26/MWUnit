<?php

namespace MWUnit;

use MWUnit\ParserFunction\TemplateMockParserFunction;
use MWUnit\Runner\BaseTestRunner;
use Title;

class TemplateMockStore {
	/**
	 * @var TemplateMockStore
	 */
	private static $instance = null;

	/**
	 * Key value pair, where the key is the page ID for the page that is mocked and
	 * the value is the mock value.
	 *
	 * @var string[]
	 */
	private $mocks = [];

	/**
	 * TemplateMockStore constructor.
	 *
	 * Injects itself into dependencies that require it.
	 */
	private function __construct() {
		TemplateMockParserFunction::setTemplateMockStore( $this );
		BaseTestRunner::setTemplateMockStore( $this );
	}

	/**
	 * Returns the instance of TemplateMockRegistry.
	 *
	 * @return TemplateMockStore
	 */
	public static function getInstance(): TemplateMockStore {
		self::instantiate();
		return self::$instance;
	}

	/**
	 * Instantiates the TemplateMockStore.
	 */
	public static function instantiate() {
		if ( !isset( self::$instance ) ) {
			self::$instance = new self();
		}
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
	 * @return string|null
	 */
	public function get( Title $title ) {
		return $this->mocks[ $title->getArticleID() ] ?? null;
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
