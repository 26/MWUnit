<?php

namespace MWUnit\Special\Form;

use HTMLForm;
use MWUnit\Special\Callback\SubmitCallback;
use MWUnit\Special\Callback\ValidationCallback;
use OutputPage;

/**
 * Class AbstractForm
 *
 * @package MWUnit\Special\Form
 */
abstract class AbstractForm {
	/**
	 * @var OutputPage
	 */
	private $page;

	/**
	 * @var SubmitCallback
	 */
	private $submit_callback;

	/**
	 * @var ValidationCallback
	 */
	private $validation_callback;

	/**
	 * AbstractForm constructor.
	 *
	 * @param OutputPage $page
	 * @param SubmitCallback|null $submit_callback
	 * @param ValidationCallback|null $validation_callback
	 */
	public function __construct(
		OutputPage $page,
		SubmitCallback $submit_callback = null,
		ValidationCallback $validation_callback = null
	) {
		$this->page = $page;
		$this->submit_callback = $submit_callback;
		$this->validation_callback = $validation_callback;
	}

	/**
	 * Sets this form's submit callback.
	 *
	 * @param SubmitCallback $callback
	 */
	public function setSubmitCallback( SubmitCallback $callback ) {
		$this->submit_callback = $callback;
	}

	/**
	 * Sets this form's validation callback.
	 *
	 * @param ValidationCallback $callback
	 */
	public function setValidationCallback( ValidationCallback $callback ) {
		$this->validation_callback = $callback;
	}

	/**
	 * Returns the submit callback for this form.
	 *
	 * @return SubmitCallback
	 */
	public function getSubmitCallback() {
		return $this->submit_callback;
	}

	/**
	 * Returns the validation callback for this form.
	 *
	 * @return ValidationCallback
	 */
	public function getValidationCallback() {
		return $this->validation_callback;
	}

	/**
	 * Returns the form.
	 *
	 * @return HTMLForm
	 */
	public function getForm(): HTMLForm {
		$form = HTMLForm::factory( 'ooui', $this->getDescriptor(), $this->page->getContext() );

		$form->setWrapperLegendMsg( $this->getWrapperLegendMessage() );
		$form->setSubmitTextMsg( $this->getSubmitTextMessage() );
		$form->setSubmitCallback( [ $this->getSubmitCallback(), 'onSubmit' ] );
		$form->setMessagePrefix( $this->getFormIdentifier() );
		$form->setFormIdentifier( $this->getFormIdentifier() );
		$form->setMethod( $this->getMethod() );

		if ( $this->isDestructive() ) {
			$form->setSubmitDestructive();
		}

		return $form;
	}

	/**
	 * Shows this form.
	 *
	 * @return void
	 */
	public function show() {
		$this->getForm()->show();
	}

	/**
	 * Returns the name of the system message to show as the wrapper legend.
	 *
	 * @return string The system message name to show as the wrapper legend.
	 * @stable to override
	 */
	public function getWrapperLegendMessage() {
		return '';
	}

	/**
	 * Returns the method to use when submitting this form. Should be either
	 * "get" or "post".
	 *
	 * @return string
	 * @stable to override
	 */
	public function getMethod(): string {
		return 'get';
	}

	/**
	 * Returns true if and only if this form is (or can be) destructive.
	 *
	 * @return bool
	 * @stable to override
	 */
	public function isDestructive(): bool {
		return false;
	}

	/**
	 * Returns this form's descriptor.
	 *
	 * @return array
	 */
	abstract public function getDescriptor(): array;

	/**
	 * Returns this form's submit text message.
	 *
	 * @return string
	 */
	abstract public function getSubmitTextMessage(): string;

	/**
	 * Returns the unique identifier for this form.
	 *
	 * @return string
	 */
	abstract public function getFormIdentifier(): string;
}
