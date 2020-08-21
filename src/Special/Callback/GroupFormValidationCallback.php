<?php

namespace MWUnit\Special\Callback;

/**
 * Class GroupFormValidationCallback
 *
 * @package MWUnit\Special\Callback
 */
class GroupFormValidationCallback implements ValidationCallback {
    /**
     * @return array
     */
    public static function getValidGroups() {
        $database = wfGetDb( DB_REPLICA );
        $result = $database->select(
            'mwunit_tests',
            'test_group',
            [],
            'Database::select',
            'DISTINCT'
        );

        $row_count = $result->numRows();

        $descriptor = [];
        for ( $i = 0; $i < $row_count; $i++ ) {
            $row = $result->current();
            $descriptor[ $row->test_group ] = $row->test_group;

            $result->next();
        }

        return $descriptor;
    }

    /**
     * @inheritDoc
     */
    public function validateField(string $field, $value, array $form_data) {
        if ( $field !== 'test_group' ) return false;

        return in_array( $value, self::getValidGroups() );
    }
}