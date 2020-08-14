<?php

namespace MWUnit\Registry;

use MWUnit\Exception\MWUnitException;
use MWUnit\Mock\MockInterface;
use MWUnit\MWUnit;
use Title;

class TemplateMockRegistry implements Registry {
    protected static $instance = null;

	/**
	 * Key value pair, where the key is the page ID for the page that is mocked and
	 * the value is the mock value.
	 *
	 * @var array
	 */
	private $mocks = [];

	private function __construct() {}

    /**
     * @inheritDoc
     * @return TemplateMockRegistry
     */
    public static function getInstance(): Registry {
        self::setInstance();
        return self::$instance;
    }

    /**
     * @inheritDoc
     */
    public static function setInstance() {
        if ( !isset( self::$instance ) ) {
            self::$instance = new self();
        }
    }

	/**
	 * Returns true if and only if the given Title is mocked.
	 *
	 * @param Title $title
	 * @return bool
     * @inheritDoc
	 */
	public function exists( $title ): bool {
		return isset( $this->mocks[ $title->getArticleID() ] );
	}

    /**
     * Returns the value of the given mock or null if the given
     * page is not mocked.
     *
     * @param Title $title
     * @return MockInterface
     * @throws MWUnitException
     * @inheritDoc
     */
	public function get( $title ) {
		if ( !$this->exists( $title ) ) {
		    throw new MWUnitException( "{$title->getFullText()} is not mocked" );
		}

		return $this->mocks[ $title->getArticleID() ];
	}

    /**
     * Registers a new mock or overwrites an existing mock.
     *
     * @param Title $title
     * @param MockInterface $content
     * @inheritDoc
     */
	public function register( $title, $content ) {
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
