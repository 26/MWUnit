<?php

namespace MWUnit\API;

use ApiBase;
use ApiUsageException;
use MWUnit\Exception\MWUnitException;
use MWUnit\TestSuite;

class ApiQueryUnitTests extends \ApiQueryBase {
	/**
	 * @inheritDoc
	 *
	 * @throws ApiUsageException
	 * @throws MWUnitException
	 */
	public function execute() {
		$this->checkUserRightsAny( 'read' );

		$this->requireAtLeastOneParameter(
			$this->extractRequestParams(),
			'group',
			'page',
			'covers'
		);

		$test_cases = [];

		if ( $group = $this->getParameter( 'group' ) ) {
			$test_suite = TestSuite::newFromGroup( $group );
			foreach ( $test_suite as $test_class ) {
				foreach ( $test_class->getTestCases() as $test_case ) {
					$test_cases[] = $test_case;
				}
			}
		}

		if ( $page = $this->getParameter( 'page' ) ) {
			if ( strpos( $page, ":" ) !== false ) {
				// A namespace is specified
				$title = \Title::newFromText( $page );
			} else {
				$title = \Title::newFromText( $page, NS_TEST );
			}

			if ( !$title instanceof \Title ) {
				$this->dieWithError( 'mwunit-api-fatal-invalid-title' );
			}

			$test_suite = TestSuite::newFromTitle( $title );
			foreach ( $test_suite as $test_class ) {
				foreach ( $test_class->getTestCases() as $test_case ) {
					$test_cases[] = $test_case;
				}
			}
		}

		if ( $covers = $this->getParameter( 'covers' ) ) {
			$test_suite = TestSuite::newFromCovers( $covers );
			foreach ( $test_suite as $test_class ) {
				foreach ( $test_class->getTestCases() as $test_case ) {
					$test_cases[] = $test_case;
				}
			}
		}

		$api_result = $this->getResult();

		foreach ( $test_cases as $test_case ) {
			$id = $test_case->getCanonicalName();

			$api_result->addValue( $id, "name", $test_case->getTestName() );
			$api_result->addValue( $id, "group", $test_case->getTestGroup() );
			$api_result->addValue( $id, "article_id", $test_case->getTestPage()->getArticleID() );
			$api_result->addValue( $id, "article_text", $test_case->getTestPage()->getFullText() );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'group' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_HELP_MSG => 'mwunit-api-group-param'
			],
			'page' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_HELP_MSG => 'mwunit-api-page-param'
			],
			'covers' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_HELP_MSG => 'mwunit-api-covers-param'
			]
		];
	}
}
