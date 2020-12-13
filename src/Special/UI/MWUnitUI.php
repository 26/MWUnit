<?php

namespace MWUnit\Special\UI;

use HtmlArmor;
use MediaWiki\Linker\LinkRenderer;
use MWUnit\Renderer\Tag;
use OutputPage;
use Title;
use Xml;

/**
 * Class MWUnitUI
 *
 * @package MWUnit\Special\UI
 * @stable to extend
 */
abstract class MWUnitUI {
	/**
	 * @var OutputPage
	 */
	private $output;

	/**
	 * @var LinkRenderer
	 */
	private $link_renderer;

	/**
	 * @var string
	 */
	private $parameter = '';

	/**
	 * MWUnitUI constructor.
	 * @param OutputPage $output
	 * @param LinkRenderer $link_renderer
	 * @stable to call
	 */
	public function __construct( OutputPage $output, LinkRenderer $link_renderer ) {
		$this->output = $output;
		$this->link_renderer = $link_renderer;
	}

	/**
	 * Sets the parameter (subpage name) for this UI.
	 *
	 * @param string $parameter
	 * @stable to call
	 */
	public function setParameter( string $parameter ) {
		$this->parameter = $parameter;
	}

	/**
	 * Returns the parameter (subpage name) for this UI.
	 *
	 * @return string
	 * @stable to call
	 */
	public function getParameter(): string {
		return $this->parameter;
	}

	/**
	 * @return OutputPage
	 * @stable to call
	 */
	public function getOutput(): OutputPage {
		return $this->output;
	}

	/**
	 * @return LinkRenderer
	 * @stable to call
	 */
	public function getLinkRenderer(): LinkRenderer {
		return $this->link_renderer;
	}

	/**
	 * Executes the class and renders the UI.
	 * @stable to call
	 */
	public function execute() {
		$this->preRender();
		$this->render();
		$this->postRender();
	}

	/**
	 * Executed before the main render() method is executed.
	 * @internal
	 */
	private function preRender() {
		$this->output->enableOOUI();
		$this->output->preventClickjacking();
		$this->output->clearHTML();
	}

	/**
	 * Executed after the main render() method is executed.
	 * @internal
	 */
	private function postRender() {
		$this->loadModules();
		$this->renderHeader();
		$this->renderNavigation();
	}

	/**
	 * Loads the specified modules.
	 * @internal
	 */
	private function loadModules() {
		$this->getOutput()->addModules( $this->getModules() );
	}

	/**
	 * Renders the header specified via $this->getHeader().
	 * @internal
	 */
	private function renderHeader() {
		$this->getOutput()->setPageTitle(
			( new Tag( "div", $this->getHeader(), [ "class" => "title" ] ) )->__toString()
		);
	}

	/**
	 * Renders the navigation menu.
	 * @internal
	 */
	private function renderNavigation() {
		$link_definitions = $this->getNavigationItems();

		if ( empty( $link_definitions ) ) {
			return;
		}

		$links = array_map( function ( $key, $value )  {
			$title = Title::newFromText( $value );

			if ( $this->getParameter() === $key ) {
				return ( new Tag( "strong", $key ) );
			}

			return $this->getLinkRenderer()->makeLink( $title, new HtmlArmor( $key ) );
		}, array_keys( $link_definitions ), array_values( $link_definitions ) );

		$nav = wfMessage( 'parentheses' )
			->rawParams( $this->getOutput()->getLanguage()->pipeList( $links ) )
			->text();
		$nav = $this->getNavigationPrefix() . " $nav";
		$nav = Xml::tags( 'div', [ 'class' => 'mw-mwunit-special-navigation' ], $nav );
		$this->getOutput()->setSubtitle( $nav );
	}

	/**
	 * Returns the navigation prefix shown on the navigation menu.
	 *
	 * @return string
	 * @stable to override
	 */
	public function getNavigationPrefix(): string {
		return '';
	}

	/**
	 * Returns an array of modules that must be loaded.
	 *
	 * @return array
	 * @stable to override
	 */
	public function getModules(): array {
		return [];
	}

	/**
	 * Returns the elements in the navigation menu. These elements take the form of a key-value pair,
	 * where the key is the text shown as the hyperlink, and the value is the page name.
	 *
	 * @return array
	 * @stable to override
	 */
	public function getNavigationItems(): array {
		return [];
	}

	/**
	 * Renders the UI.
	 *
	 * @return void
	 * @stable to override
	 */
	abstract public function render();

	/**
	 * Returns the header text shown in the UI.
	 *
	 * @return string
	 * @stable to override
	 */
	abstract public function getHeader(): string;

	/**
	 * @return string
	 */
	abstract public function getClass(): string;
}
