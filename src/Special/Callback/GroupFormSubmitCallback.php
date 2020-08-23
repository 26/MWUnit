<?php

namespace MWUnit\Special\Callback;

use MediaWiki\Linker\LinkRenderer;
use MWException;
use MWUnit\Exception\MWUnitException;
use MWUnit\Runner\TestSuiteRunner;
use MWUnit\Special\UI\ExceptionUI;
use MWUnit\Special\UI\ResultUI;
use MWUnit\TestSuite;

/**
 * Class GroupFormSubmitCallback
 *
 * @package MWUnit\Special\Callback
 */
class GroupFormSubmitCallback implements SubmitCallback {
    /**
     * @var \OutputPage
     */
    private $output_page;

    /**
     * @var LinkRenderer
     */
    private $link_renderer;

    /**
     * GroupFormSubmitCallback constructor.
     *
     * @param \OutputPage $output_page
     * @param LinkRenderer $link_renderer
     */
    public function __construct( \OutputPage $output_page, LinkRenderer $link_renderer ) {
        $this->output_page = $output_page;
        $this->link_renderer = $link_renderer;
    }

    /**
     * @inheritDoc
     */
    public function onSubmit( array $form_data ) {
        $group = $form_data['test_group'];
        $test_suite = TestSuite::newFromGroup( $group );
        $runner = new TestSuiteRunner( $test_suite, null );

        try {
            $runner->run();
        } catch( MWUnitException $e ) {
            $ui = new ExceptionUI( $e, $this->output_page, $this->link_renderer );
            $ui->execute();

            return;
        }

        $output_page = $this->output_page;
        $link_renderer = $this->link_renderer;

        $ui = new ResultUI( $runner, $output_page, $link_renderer );
        $ui->execute();
    }
}