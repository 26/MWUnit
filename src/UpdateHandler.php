<?php

namespace MWUnit;

use Content;
use LogEntry;
use MWUnit\Exception\InvalidTestPageException;
use Revision;
use Status;
use User;
use WikiPage;

/**
 * Class UpdateHandler
 *
 * @package MWUnit
 */
class UpdateHandler {
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
		self::deregisterTestsOnPage( $article_id );

		try {
			$content = $wikiPage->getContent( Revision::FOR_THIS_USER );
			$wikitext = $wikiPage->getContentHandler()->serializeContent( $content );

			$test_class = TestClass::newFromWikitext( $wikitext, $wikiPage->getTitle() );
			$test_class->doUpdate();
		} catch ( InvalidTestPageException $e ) {
			MWUnit::getLogger()->warning(
				"Invalid test case(s) on test page {page}: {e}",
				[ "page" => $wikiPage->getTitle()->getFullText(), "e" => $e->getMessage() ]
			);
		}

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

		self::deregisterTestsOnPage( $deleted_id );

		return true;
	}

	/**
	 * Removes all test cases on a page from the database.
	 *
	 * @param int $article_id The article ID of the page from which the tests should be deregistered.
	 */
	private static function deregisterTestsOnPage( int $article_id ) {
		$database = wfGetDb( DB_MASTER );

		$database->delete(
			'mwunit_tests',
			[ 'article_id' => $article_id ]
		);

		$database->delete(
			'mwunit_attributes',
			[ 'article_id' => $article_id ]
		);

		$database->delete(
			'mwunit_setup',
			[ 'article_id' => $article_id ]
		);

		$database->delete(
			'mwunit_teardown',
			[ 'article_id' => $article_id ]
		);

		$database->delete(
			'mwunit_content',
			[ 'article_id' => $article_id ]
		);
	}
}
