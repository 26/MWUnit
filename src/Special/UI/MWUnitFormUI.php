<?php


namespace MWUnit\Special\UI;

use MediaWiki\Linker\LinkRenderer;
use MWUnit\Special\Callback\FauxValidationCallback;
use MWUnit\Special\Callback\GroupFormSubmitCallback;
use MWUnit\Special\Callback\GroupFormValidationCallback;
use MWUnit\Special\Callback\IndividualFormSubmitCallback;
use MWUnit\Special\Callback\IndividualFormValidationCallback;
use MWUnit\Special\Form\GroupForm;
use MWUnit\Special\Form\IndividualForm;
use OutputPage;
use WebRequest;

/**
 * Class MWUnitFormUI
 * @package MWUnit\Special\UI
 */
class MWUnitFormUI extends MWUnitUI {
    /**
     * @var WebRequest
     */
    private $request;

    public function __construct( WebRequest $request, OutputPage $output, LinkRenderer $link_renderer ) {
        $this->request = $request;

        parent::__construct( $output, $link_renderer );
    }

    public function render() {
        $submit_callback = new GroupFormSubmitCallback( $this->getOutput(), $this->getLinkRenderer() );
        $validation_callback = new GroupFormValidationCallback();
        $group_form = new GroupForm( $this->getOutput(), $submit_callback, $validation_callback );

        $submit_callback = new IndividualFormSubmitCallback( $this->getOutput(), $this->getLinkRenderer() );
        $validation_callback = new IndividualFormValidationCallback();
        $individual_form = new IndividualForm( $this->getOutput(), $submit_callback, $validation_callback );

        if ( !$this->request->wasPosted() ) {
            $group_form->show();
            $individual_form->show();

            return;
        }

        $form = $this->request->getVal( "unitTestGroup" ) ? $group_form : $individual_form;
        $form->show();
    }

    public function getHeader(): string {
        return wfMessage( 'mwunit-special-title' )->plain();
    }
}