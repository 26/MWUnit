<?php

namespace MWUnit\Special\Form;

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
				'name' => 'group',
				'type' => 'select',
				'label-message' => 'mwunit-special-group-label',
				'options' => $this->getOptions()
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

	/**
	 * Gets the options for the selector.
	 */
	private function getOptions() {
		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select(
			'mwunit_tests',
			[ 'test_group' ]
		);

		$buffer = [];

		foreach ( $result as $item ) {
			$group = $item->test_group;
			$buffer[$group] = $group;
		}

		return $buffer;
	}
}
