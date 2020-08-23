<?php

namespace MWUnit\API;

use ApiBase;
use MWUnit\Exception\MWUnitException;
use MWUnit\Runner\TestSuiteRunner;
use MWUnit\TestSuite;

class ApiRunUnitTests extends ApiBase {
    /**
     * @inheritDoc
     */
    public function execute() {
        $this->checkUserRightsAny( 'mwunit-runtests' );

        // Require at least one test or suite to be specified.
        $this->requireAtLeastOneParameter(
            $this->extractRequestParams(),
            'group',
            'test',
            'page',
            'covers'
        );

        $group_test_suite = TestSuite::newEmpty();
        $test_test_suite = TestSuite::newEmpty();
        $page_test_suite = TestSuite::newEmpty();
        $covers_test_suite = TestSuite::newEmpty();

        if ( $group = $this->getParameter( 'group' ) ) {
            $group_test_suite = TestSuite::newFromGroup( $group );
        }

        if ( $test = $this->getParameter( 'test' ) ) {
            try {
                $test_test_suite = TestSuite::newFromText( $test );
            } catch ( MWUnitException $e ) {
                $this->dieWithError( 'mwunit-api-fatal-invalid-test-name' );
            }
        }

        if ( $page = $this->getParameter( 'page' ) ) {
            $title = \Title::newFromText( $page );

            if ( !$title instanceof \Title ) {
                $this->dieWithError( 'mwunit-api-fatal-invalid-title' );
            }

            $page_test_suite = TestSuite::newFromTitle( $title );
        }

        if ( $covers = $this->getParameter( 'covers' ) ) {
            $covers_test_suite = TestSuite::newFromCovers( $covers );
        }

        $test_suite = $group_test_suite->merge( $test_test_suite, $page_test_suite, $covers_test_suite );

        $runner = new TestSuiteRunner( $test_suite );

        try {
            $runner->run();
        } catch ( MWUnitException $e ) {
            $this->dieWithException( $e );
        }

        $test_run_store = $runner->getTestRunStore();
        $api_result     = $this->getResult();

        foreach ( $test_run_store->getAll() as $idx => $test_run ) {
            $id = $test_run->getTestCase()->__toString();

            $result     = $test_run->getResult();
            $output     = $test_run->getTestOutputCollector();
            $test_case  = $test_run->getTestCase();
            $covered    = $test_run->getCovered();
            $assertions = $test_run->getAssertionCount();

            $result_path = [ $id, "result" ];

            $api_result->addValue( $result_path, "code", $result->getResult() );
            $api_result->addValue( $result_path, "message", $result->getMessage() );

            $metadata_path = [ $id, "metadata" ];

            $api_result->addValue( $metadata_path, "name", $test_case->getName() );
            $api_result->addValue( $metadata_path, "group", $test_case->getGroup() );
            $api_result->addValue( $metadata_path, "title", $test_case->getTitle() );
            $api_result->addValue( $metadata_path, "assertions", $assertions );
            $api_result->addValue( $metadata_path, "covers", $covered );

            $output_path = [ $id, "output" ];

            foreach ( $output as $out ) {
                $api_result->addValue( $output_path, null, $out->getOutput() );
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
}