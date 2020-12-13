<?php

namespace MWUnit;

/**
 * Interface TemplateMockStoreInjector
 * @package MWUnit\Injector
 */
interface TemplateMockStoreInjector {
	/**
	 * Dependency injector for the TemplateMockStore object.
	 *
	 * @param TemplateMockStore $store
	 */
	public static function setTemplateMockStore( TemplateMockStore $store );
}
