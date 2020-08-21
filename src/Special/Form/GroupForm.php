<?php

namespace MWUnit\Special\Form;

use MWUnit\Special\Callback\GroupFormValidationCallback;

/**
 * Class GroupForm
 *
 * @package MWUnit\Special\Form
 */
class GroupForm extends AbstractForm {
    /**
     * @inheritDoc
     */
    public function getDescriptor(): array {
        return [
            'test_group' => [
                'name' => 'unitTestGroup',
                'type' => 'select',
                'label-message' => 'mwunit-special-group-label',
                'options' => GroupFormValidationCallback::getValidGroups(),
                'validation-callback' => function ( $value, array $data ) {
                    return $this->getValidationCallback()->validateField( 'test_group', $value, $data );
                }
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getWrapperLegendMessage() {
        return 'mwunit-group-test-legend';
    }

    /**
     * @inheritDoc
     */
    public function getSubmitTextMessage(): string {
        return 'mwunit-run-tests-button';
    }

    /**
     * @inheritDoc
     */
    public function getFormIdentifier(): string {
        return 'group-test-run-form';
    }
}