<?php

namespace MWUnit\Special\Form;

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
				'name' => 'test',
				'type' => 'select',
				'label-message' => 'mwunit-special-individual-label',
				'options' => $this->getOptions()
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

	/**
	 * Gets the options for the selector.
	 */
	private function getOptions() {
		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select(
			'mwunit_tests',
			[ 'article_id', 'test_name' ]
		);

		$buffer = [];

		foreach ( $result as $item ) {
			$name = $item->test_name;
			$article_id = $item->article_id;

			$name = \Title::newFromID( $article_id )->getText() . "::" . $name;

			$buffer[$name] = $name;
		}

		return $buffer;
	}
}
