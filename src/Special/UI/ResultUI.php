<?php

namespace MWUnit\Special\UI;

use HtmlArmor;
use MediaWiki\Linker\LinkRenderer;
use MWUnit\MWUnit;
use MWUnit\Runner\Result\TestResult;
use MWUnit\Runner\TestRun;
use MWUnit\Runner\TestSuiteRunner;
use MWUnit\Store\TestOutputStore;
use OutputPage;

class ResultUI extends MWUnitUI {
    /**
     * @var TestSuiteRunner
     */
    private $runner;

    /**
     * ResultUI constructor.
     *
     * @param TestSuiteRunner $runner
     * @param OutputPage $output
     * @param LinkRenderer $link_renderer
     *
     * @inheritDoc
     */
    public function __construct( TestSuiteRunner $runner, OutputPage $output, LinkRenderer $link_renderer ) {
        $this->runner = $runner;

        parent::__construct( $output, $link_renderer );
    }

    /**
     * @inheritDoc
     */
    public function render() {
        if ( count( $this->runner->getTestRunStore()->getAll() ) === 0 ) {
            $this->getOutput()->showErrorPage( 'mwunit-generic-error-title', 'mwunit-generic-error-description' );
            return;
        }

        $test_count      = $this->runner->getTestCount();
        $assertion_count = $this->runner->getTotalAssertionsCount();
        $failures_count  = $this->runner->getNotPassedCount();

        $this->getOutput()->addHTML(
            \Xml::tags( 'p', [], wfMessage( 'mwunit-test-result-intro' )->plain() )
        );

        $this->getOutput()->addHTML(
            \Xml::tags( 'p', [], \Xml::tags( 'b', [], wfMessage(
                'mwunit-test-result-summary',
                $test_count,
                $assertion_count,
                $failures_count
            )->plain() ) )
        );

        $store = $this->runner->getTestRunStore();
        $store->sort();

        foreach ( $store as $test_run ) {
            $this->getOutput()->addHTML( $this->renderTest( $test_run ) );
        }
    }

    /**
     * @inheritDoc
     */
    public function getHeader(): string {
        return wfMessage( 'mwunit-special-result-title' )->plain();
    }

    /**
     * @inheritDoc
     */
    public function getNavigationPrefix(): string {
        return wfMessage( 'mwunit-nav-introtext' )->plain();
    }

    /**
     * @inheritDoc
     */
    public function getNavigationItems(): array {
        return [
            wfMessage( 'mwunit-nav-home' )->plain() => "Special:MWUnit"
        ];
    }

    /**
     * Renders the given TestResult object.
     *
     * @param TestRun $run
     * @return string
     */
    private function renderTest( TestRun $run ): string {
        switch ( $run->getResult()->getResult() ) {
            case TestResult::T_RISKY:
                return $this->renderRiskyTest( $run );
            case TestResult::T_FAILED:
                return $this->renderFailedTest( $run );
            case TestResult::T_SUCCESS:
                return $this->renderSucceededTest( $run );
        }

        // To stop PHP from complaining.
        return '';
    }

    /**
     * Renders a risky test.
     *
     * @param TestRun $run
     * @return string
     */
    private function renderRiskyTest( TestRun $run ) {
        return sprintf(
            '<div class="warningbox" style="display:block;">' .
            '<p><span style="color:#fc3"><b>%s</b></span> %s</p>%s' .
            '</div>',
            wfMessage( 'mwunit-test-risky' )->plain(),
            $this->formatTestHeader( $run->getResult() ),
            $this->formatSummary( $run )
        );
    }

    /**
     * Renders a failed test.
     *
     * @param TestRun $run
     * @return string
     */
    private function renderFailedTest( TestRun $run ) {
        return sprintf(
            '<div class="errorbox" style="display:block;">' .
            '<p><span style="color:#d33"><b>%s</b></span> %s</p>%s' .
            '</div>',
            wfMessage( 'mwunit-test-failed' )->plain(),
            $this->formatTestHeader( $run->getResult() ),
            $this->formatSummary( $run )
        );
    }

    /**
     * Renders a succeeded test.
     *
     * @param TestRun $run
     * @return string
     */
    private function renderSucceededTest( TestRun $run ) {
        return sprintf(
            '<div class="successbox" style="display:block;">' .
            '<p><span style="color:#14866d"><b>%s</b></span> %s</p>%s' .
            '</div>',
            wfMessage( 'mwunit-test-success' )->plain(),
            $this->formatTestHeader( $run->getResult() ),
            $this->formatSummary( $run )
        );
    }

    /**
     * Formats the header for the given TestResult object.
     *
     * @param TestResult $result
     * @return string|null
     */
    private function formatTestHeader( TestResult $result ) {
        $page_name = $result->getPageName();
        $test_name = $result->getTestName();

        $test_title = MWUnit::testNameToSentence( $test_name );

        $title = \Title::newFromText( $page_name );
        $link = $this->getLinkRenderer()->makeLink( $title, new HtmlArmor( $result->getTestCase() ) );

        return sprintf(
            "%s (<code>%s</code>)",
            $test_title,
            $link
        );
    }

    /**
     * Formats the summary for the given TestRun object.
     *
     * @param TestRun $run
     * @return string
     */
    private function formatSummary( TestRun $run ): string {
        $message = $run->getResult()->getMessage();
        $collector = $run->getTestOutputCollector();
        $test_output = $this->formatTestOutput( $collector );

        if ( !$message && !$test_output ) {
            return '';
        }

        if ( !$message && $test_output ) {
            return "<hr/><pre>The test outputted the following:\n\n$test_output</pre>";
        }

        $output = "<hr/><pre>$message";

        if ( $test_output ) {
            $output .= "\n\nIn addition, the test outputted the following:\n\n$test_output";
        }

        $output .= "</pre>";

        return $output;
    }

    /**
     * Formats the given TestOutputStore as a string.
     *
     * @param TestOutputStore $store
     * @return string
     */
    private function formatTestOutput( TestOutputStore $store ): string {
        return count( $store->getAll() ) === 0 ?
            '' :
            implode( "\n", $store->getAll() );
    }
}