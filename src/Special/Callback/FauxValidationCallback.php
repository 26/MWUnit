<?php

namespace MWUnit\Special\Callback;

/**
 * Class FauxValidationCallback
 *
 * This validation callback always returns true.
 *
 * @package MWUnit\Special\Callback
 */
class FauxValidationCallback implements ValidationCallback {
    /**
     * Validates whether the given value is allowed for the given field.
     *
     * @param string $field
     * @param string|array $value
     * @param array $form_data
     * @return bool|string
     */
    public function validateField( string $field, $value, array $form_data ) {
        return true;
    }
}