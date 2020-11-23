<?php

namespace MWUnit;

use Content;
use DOMDocument;
use DOMNode;
use LogEntry;
use MWUnit\Injector\TestCaseStoreInjector;
use MWUnit\TestCaseRepository;
use Revision;
use Status;
use User;
use WikiPage;

/**
 * Class UpdateHandler
 *
 * @package MWUnit
 */
class UpdateHandler  {
    /**
     * Occurs after the save page request has been processed.
     *
     * @param WikiPage $wikiPage
     * @param User $user
     * @param Content $content
     * @param string $summaryText
     * @param bool $isMinor
     * @param null $isWatch Unused
     * @param null $section Unused
     * @param int $flags
     * @param Revision|null $revision
     * @param Status $status
     * @param int|false $originalRevId
     * @param int $undidRevId
     *
     * @return bool
     * @throws \MWException
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
     */
	public static function onPageContentSaveComplete(
		WikiPage $wikiPage,
		User $user,
		Content $content,
		string $summaryText,
		bool $isMinor,
		$isWatch,
		$section,
		int $flags,
		$revision,
		Status $status,
		$originalRevId,
		int $undidRevId
	) {
		if ( $wikiPage->getTitle()->getNamespace() !== NS_TEST ) {
			// Do not run hook outside of "Test" namespace
			return true;
		}

		$article_id = $wikiPage->getTitle()->getArticleID();

		MWUnit::getLogger()->debug( 'Deregistering tests for article {id} because the page got updated', [
			'id' => $article_id
		] );

		// Deregister all tests on the page and let the parser re-register them.
        TestCaseRepository::getInstance()->deregisterTestsOnPage( $article_id );
        $test_class = TestClass::newFromWikipage( $wikiPage );
        TestCaseRepository::getInstance()->registerTestClass( $test_class );

		return true;
	}

	/**
	 * Gets executed when an article (page) has been deleted. Deletes are records associated
	 * with that page.
	 *
	 * @param WikiPage &$article
	 * @param User &$user
	 * @param string $reason
	 * @param int $id
	 * @param string|null $content
	 * @param LogEntry $logEntry
	 * @param int $archivedRevisionCount
	 *
	 * @return bool
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 */
	public static function onArticleDeleteComplete(
		WikiPage &$article,
		User &$user,
		$reason,
		$id,
		$content,
		LogEntry $logEntry,
		$archivedRevisionCount
	) {
        if ( $article->getTitle()->getNamespace() !== NS_TEST ) {
            // Do not run hook outside of "Test" namespace
            return true;
        }

		$deleted_id = $article->getId();

		MWUnit::getLogger()->debug( 'Deregistering tests for article {id} because the page got deleted', [
			'id' => $deleted_id
		] );

		TestCaseRepository::getInstance()->deregisterTestsOnPage( $deleted_id );

		return true;
	}
}
