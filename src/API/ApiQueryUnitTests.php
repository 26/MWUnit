<?php

namespace MWUnit\API;

use ApiBase;
use ApiQueryBase;
use MWUnit\TestCase;
use MWUnit\TestCaseRepository;

class ApiQueryUnitTests extends ApiQueryBase {
    /**
     * @inheritDoc
     */
    public function execute() {
        $this->checkUserRightsAny( 'read' );

        $this->requireAtLeastOneParameter(
            $this->extractRequestParams(),
            'group',
            'page',
            'covers'
        );

        $repository = TestCaseRepository::getInstance();

        $group_tests    = [];
        $page_tests     = [];
        $covers_tests   = [];

        if ( $group = $this->getParameter( 'group' ) ) {
            $group_tests = $repository->getTestsFromGroup( $group );
            $group_tests = iterator_to_array( $group_tests );
        }

        if ( $page = $this->getParameter( 'page' ) ) {
            $title = \Title::newFromText( $page );

            if ( !$title instanceof \Title ) {
                $this->dieWithError( 'mwunit-api-fatal-invalid-title' );
            }

            $page_tests = $repository->getTestsFromTitle( $title );
            $page_tests = iterator_to_array( $page_tests );
        }

        if ( $covers = $this->getParameter( 'covers' ) ) {
            $covers_tests = $repository->getTestsCoveringTemplate( $covers );
            $covers_tests = iterator_to_array( $covers_tests );
        }

        $tests = array_merge( $group_tests, $page_tests, $covers_tests );
        $api_result = $this->getResult();

        foreach ( $tests as $row ) {
            $test_case = TestCase::newFromRow( $row );

            $id =  "{$test_case->getTitle()->getText()}::{$test_case->getName()}";

            $api_result->addValue( $id, "name", $test_case->getName() );
            $api_result->addValue( $id, "group", $test_case->getGroup() );
            $api_result->addValue( $id, "article_id", $test_case->getTitle()->getArticleID() );
            $api_result->addValue( $id, "article_text", $test_case->getTitle() );
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