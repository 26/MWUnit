<?php

namespace MWUnit\Registry;

use MWUnit\MWUnit;
use Title;

class MockRegistry {
	/**
	 * Key value pair, where the key is the page ID for the page that is mocked and
	 * the value is the mock value.
	 *
	 * @var array
	 */
	private $mocks = [];

	/**
	 * @var MockRegistry|null
	 */
	private static $instance = null;

	/**
	 * MockRegistry constructor.
	 */
	private function __construct() {
	}

	/**
	 * Gets the instance of the MockRegistry.
	 *
	 * @return MockRegistry
	 */
	public static function getInstance() {
		if ( self::$instance === null ) { self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Returns true if and only if the given Title is mocked.
	 *
	 * @param Title $title
	 * @return bool
	 */
	public function isMocked( Title $title ): bool {
		return isset( $this->mocks[ $title->getArticleID() ] );
	}

	/**
	 * Returns the value of the given mock or null if the given
	 * page is not mocked.
	 *
	 * @param Title $title
	 * @return string
	 */
	public function getMock( Title $title ): string {
		if ( !$this->isMocked( $title ) ) { return null;
		}
		return $this->mocks[ $title->getArticleID() ];
	}

	/**
	 * Registers a new mock or overwrites an existing mock.
	 *
	 * @param Title $title
	 * @param string $content
	 */
	public function registerMock( Title $title, string $content ) {
		$id = $title->getArticleID();
		$this->mocks[ $id ] = $content;

		MWUnit::getLogger()->notice( "Registering mock for {title}", [
			"title" => $title->getFullText()
		] );
	}
}
