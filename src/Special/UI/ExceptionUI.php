<?php

namespace MWUnit\Special\UI;

use MediaWiki\Linker\LinkRenderer;
use MWUnit\Exception\MWUnitException;
use OutputPage;

class ExceptionUI extends MWUnitUI {
    /**
     * @var MWUnitException
     */
    private $exception;

    /**
     * ExceptionUI constructor.
     * @param MWUnitException $e
     * @param OutputPage $output_page
     * @param LinkRenderer $link_renderer
     */
    public function __construct($e, OutputPage $output_page, LinkRenderer $link_renderer) {
        $this->exception = $e;

        parent::__construct( $output_page, $link_renderer );
    }

    /**
     * @inheritDoc
     */
    public function render() {
        $this->getOutput()->addWikiMsg( 'mwunit-unhandled-exception-intro' );
        $this->getOutput()->addWikiText( '== Debug information ==' );
        $this->getOutput()->addHTML(
            \Xml::tags( 'p', [], wfMessage( 'mwunit-unhandled-exception-debug-intro' ) )
        );
        $this->getOutput()->addHTML(
            \Xml::tags(
                "pre",
                [],
                $this->exception->getMessage() . "<br/><br/>" .
                $this->exception->getTraceAsString()
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getNavigationPrefix(): string {
        return wfMessage( 'mwunit-nav-introtext' )->plain();
    }

    /**
     * @inheritDoc
     */
    public function getNavigationItems(): array {
        return [
            wfMessage( 'mwunit-nav-home' )->plain() => "Special:MWUnit"
        ];
    }

    /**
     * @inheritDoc
     */
    public function getHeader(): string {
        return wfMessage( 'mwunit-exception-header' )->plain();
    }
}