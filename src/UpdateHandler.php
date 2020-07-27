<?php

namespace MWUnit;

use Content;
use LogEntry;
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
	public static function onPageContentSave(
		WikiPage $wikiPage,
		User $user,
		Content $content,
		&$summary,
		bool $isMinor,
		bool $isWatch,
		$section,
		$flags,
		$status
	) {
		$article_id = $wikiPage->getTitle()->getArticleID();

		MWUnit::getLogger()->debug( 'Deregistering tests for article {id} because the page got updated', [
			'id' => $article_id
		] );

		// Deregister all tests on the page and let the parser re-register them.
		TestCaseRegister::deregisterTests( $article_id );

		return true;
	}

	/**
	 * Occurs after the save page request has been processed.
	 *
	 * @param WikiPage $wikiPage
	 * @param User $user
	 * @param Content $mainContent
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
		Content $mainContent,
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
            return;
        }

		self::parseWikitext( $wikiPage, $mainContent );

		return true;
	}

	/**
	 * Occurs after a new article is created.
	 *
	 * @param WikiPage $wikiPage
	 * @param User $user
	 * @param $content
	 * @param $summary
	 * @param $isMinor
	 * @param $isWatch
	 * @param $section
	 * @param $flags
	 * @param Revision $revision
	 *
	 * @return bool
	 * @throws \MWException
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentInsertComplete
	 * @deprecated
	 */
	public static function onPageContentInsertComplete(
		&$wikiPage,
		User &$user,
		$content,
		$summary,
		$isMinor,
		$isWatch,
		$section,
		&$flags,
		Revision $revision
	) {
        if ( $wikiPage->getTitle()->getNamespace() !== NS_TEST ) {
            // Do not run hook outside of "Test" namespace
            return;
        }

		self::parseWikitext( $wikiPage, $content );

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
	 * @throws \MWException
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
		$deleted_id = $article->getId();

		if ( $deleted_id === null ) {
			throw new \MWException( "Deleted article ID mustn't be `null`." );
		}

		if ( $article->getTitle()->getNamespace() !== NS_TEST ) {
			// Do not run hook outside of "Test" namespace
			return true;
		}

		MWUnit::getLogger()->debug( 'Deregistering tests for article {id} because the page got deleted', [
			'id' => $deleted_id
		] );

		TestCaseRegister::deregisterTests( $deleted_id );

		return true;
	}

	/**
	 * Parses the given content in the given page's context.
	 *
	 * @param WikiPage $wikiPage
	 * @param Content $content
	 * @throws \MWException
	 */
	private static function parseWikitext( WikiPage $wikiPage, Content $content ) {
	    MWUnit::getLogger()->debug( 'Reparsing wikitext for article {id} because the page got updated', [
			'id' => $wikiPage->getTitle()->getFullText()
		] );

	    global $wgVersion;
        $context = version_compare( $wgVersion, '1.32', '<' ) ? null : 'canonical';

        $parser = \MediaWiki\MediaWikiServices::getInstance()->getParser();
        $parser->parse(
            \ContentHandler::getContentText( $content ),
            $wikiPage->getTitle(),
            \ParserOptions::newCanonical( $context )
        );
	}
}
