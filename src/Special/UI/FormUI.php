<?php

namespace MWUnit\Special\UI;

use MWUnit\Special\Form\GroupForm;
use MWUnit\Special\Form\IndividualForm;

/**
 * Class FormUI
 * @package MWUnit\Special\UI
 */
class FormUI extends MWUnitUI {
	public function render() {
		$form = new GroupForm( $this->getOutput() );
		$form->show();

		$form = new IndividualForm( $this->getOutput() );
		$form->show();
	}

	public function getHeader(): string {
		return wfMessage( "mwunit-special-title" )->parse();
	}

	public function getClass(): string {
		return self::class;
	}
}
