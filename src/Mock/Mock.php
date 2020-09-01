<?php

namespace MWUnit\Mock;

/**
 * Class Mock
 *
 * @package MWUnit\Mock
 */
class Mock implements MockInterface {
    private $content;

    /**
     * Mock constructor.
     *
     * @param string $mock_content
     */
    public function __construct( string $mock_content ) {
        $this->setMock( $mock_content );
    }

    /**
     * @inheritDoc
     */
    public function setMock( string $mock_content ) {
        $this->content = $mock_content;
    }

    /**
     * @inheritDoc
     */
    public function getMock(): string {
        return $this->content;
    }
}