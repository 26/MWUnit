<?php

namespace MWUnit\API;

use ApiBase;
use ApiUsageException;
use MWUnit\Runner\TestSuiteRunner;
use MWUnit\TestSuite;

class ApiRunUnitTests extends \ApiBase {
	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 * @throws \MWException
	 */
	public function execute() {
		$this->checkUserRightsAny( 'mwunit-runtests' );

		$test_suite = $this->getTestSuite();
		$runner = TestSuiteRunner::newFromTestSuite( $test_suite );

		$runner->run();

		$test_run_store = $runner->getTestRunStore();
		$api_result     = $this->getResult();

		foreach ( $test_run_store->getAll() as $idx => $test_run ) {
			$id = $test_run->getTestCase()->getCanonicalName();

			$result = $test_run->getResult();

			$output     = $test_run->getTestOutputs();
			$test_case  = $test_run->getTestCase();
			$covered    = $test_run->getTestCase()->getCovers();
			$assertions = $test_run->getAssertionCount();

			$result_path = [ $id, "result" ];

			$api_result->addValue( $result_path, "code", $result->getResultConstant() );
			$api_result->addValue( $result_path, "message", $result->getMessage() );

			$metadata_path = [ $id, "metadata" ];

			$api_result->addValue( $metadata_path, "name", $test_case->getName() );
			$api_result->addValue( $metadata_path, "group", $test_case->getGroup() );
			$api_result->addValue( $metadata_path, "title", $test_case->getTitle() );
			$api_result->addValue( $metadata_path, "assertions", $assertions );
			$api_result->addValue( $metadata_path, "covers", $covered );

			$output_path = [ $id, "output" ];

			foreach ( $output as $out ) {
				$api_result->addValue( $output_path, null, $out );
			}
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
			'test' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_HELP_MSG => 'mwunit-api-test-param'
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

	/**
	 * Returns the appropriate TestSuite based on the API request.
	 *
	 * @return TestSuite
	 */
	private function getTestSuite(): TestSuite {
		// Require at most AND at least one of the following parameters
		$this->requireMaxOneParameter(
			$this->extractRequestParams(),
			'group',
			'test',
			'page',
			'covers'
		);

		$this->requireOnlyOneParameter(
			$this->extractRequestParams(),
			'group',
			'test',
			'page',
			'covers'
		);

		if ( $group = $this->getParameter( 'group' ) ) {
			return TestSuite::newFromGroup( $group );
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

			return TestSuite::newFromTitle( $title );
		}

		if ( $covers = $this->getParameter( 'covers' ) ) {
			return TestSuite::newFromCovers( $covers );
		}

		// "test" must be defined, because it is required by the checks at the start of the
		// function, and none of the other variables were defined
		return TestSuite::newFromText( $this->getParameter( "test" ) );
	}
}
