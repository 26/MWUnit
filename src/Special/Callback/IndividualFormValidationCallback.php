<?php

namespace MWUnit\Special\Callback;

use MWUnit\Exception\RebuildRequiredException;

class IndividualFormValidationCallback implements ValidationCallback {
    /**
     * @return array
     * @throws RebuildRequiredException
     */
    public static function getValidTests() {
        $database = wfGetDb( DB_REPLICA );
        $result = $database->select(
            'mwunit_tests',
            [ 'article_id', 'test_name' ],
            [],
            'Database::select',
            'DISTINCT'
        );

        $row_count = $result->numRows();

        $descriptor = [];
        for ( $i = 0; $i < $row_count; $i++ ) {
            $row = $result->current();

            $title = \Title::newFromID( $row->article_id );

            if ( $title === null ) {
                throw new RebuildRequiredException( 'mwunit-rebuild-required' );
            }

            $test_identifier = $title->getText() . "::" . $row->test_name;
            $descriptor[ $test_identifier ] = $test_identifier;
            $result->next();
        }

        return $descriptor;
    }

    /**
     * @inheritDoc
     */
    public function validateField(string $field, $value, array $form_data) {
        if ( $field !== 'test_individual' ) return false;

        return in_array( $value, self::getValidTests() );
    }
}