<?php

namespace MWUnit\Special\Form;

use MWUnit\Special\Callback\IndividualFormValidationCallback;

/**
 * Class IndividualForm
 *
 * @package MWUnit\Special\Form
 */
class IndividualForm extends AbstractForm {
    /**
     * @inheritDoc
     */
    public function getDescriptor(): array {
        return [
            'test_individual' => [
                'name' => 'unitTestIndividual',
                'type' => 'select',
                'label-message' => 'mwunit-special-individual-label',
                'options' => IndividualFormValidationCallback::getValidTests(),
                'validation-callback' => function ( $value, array $data ) {
                    return $this->getValidationCallback()->validateField( 'test_individual', $value, $data );
                }
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getWrapperLegendMessage() {
        return 'mwunit-individual-test-legend';
    }

    /**
     * @inheritDoc
     */
    public function getSubmitTextMessage(): string {
        return 'mwunit-run-test-button';
    }

    /**
     * @inheritDoc
     */
    public function getFormIdentifier(): string {
        return 'individual-test-run-form';
    }
}