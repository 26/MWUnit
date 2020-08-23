<?php

namespace MWUnit\Special\Callback;

/**
 * Interface SubmitCallback
 *
 * @package MWUnit\Special\Callback
 */
interface SubmitCallback {
    /**
     * Called upon submitting a form.
     *
     * @param array $form_data The data submitted via the form.
     * @return string|bool
     */
    public function onSubmit( array $form_data );
}