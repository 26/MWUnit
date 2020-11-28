<?php

namespace MWUnit\Special\UI;

use MediaWiki\Linker\LinkRenderer;
use MWUnit\Exception\MWUnitException;
use MWUnit\Renderer\Document;
use MWUnit\Renderer\Tag;
use OutputPage;

class ExceptionUI extends MWUnitUI {
	/**
	 * @var MWUnitException
	 */
	private $exception;

	/**
	 * ExceptionUI constructor.
	 *
	 * @param \Exception $e
	 * @param OutputPage $output_page
	 * @param LinkRenderer $link_renderer
	 */
	public function __construct( \Exception $e, OutputPage $output_page, LinkRenderer $link_renderer ) {
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
			( new Tag( "p", wfMessage( 'mwunit-unhandled-exception-debug-intro' )->plain() ) )->__toString()
		);

		$this->getOutput()->addHTML(
			( new Tag( "pre", new Document( [
				new Tag( "span", $this->exception->getMessage() ),
				new Tag( "br", "" ),
				new Tag( "span", $this->exception->getTraceAsString() )
			] ), [] ) )->__toString()
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getNavigationPrefix(): string {
		return wfMessage( 'mwunit-nav-introtext' )->parse();
	}

	/**
	 * @inheritDoc
	 */
	public function getNavigationItems(): array {
		return [
			wfMessage( 'mwunit-nav-home' )->parse() => "Special:MWUnit"
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getHeader(): string {
		return wfMessage( 'mwunit-exception-header' )->parse();
	}

	/**
	 * @inheritDoc
	 */
	public function getClass(): string {
		return self::class;
	}
}
