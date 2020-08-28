<?php

namespace MWUnit\Special\Callback;

class FauxSubmitCallback implements SubmitCallback {
    public function onSubmit( array $form_data ) {
        return true;
    }
}