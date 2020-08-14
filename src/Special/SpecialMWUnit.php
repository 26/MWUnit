<?php

namespace MWUnit\Special;

use MWUnit\Exception\RebuildRequiredException;
use MWUnit\Store\TestOutputStore;
use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use MWUnit\Runner\Result\TestResult;
use MWUnit\Runner\TestRun;
use MWUnit\Runner\TestSuiteRunner;
use MWUnit\Store\TestRunStore;
use MWUnit\TestSuite;

/**
 * Class SpecialMWUnit
 *
 * @package MWUnit\Special
 */
class SpecialMWUnit extends \SpecialPage {
	/**
	 * @var TestSuiteRunner
	 */
	private $runner;

	/**
	 * SpecialMWUnit constructor.
	 * @throws \UserNotLoggedIn|\ConfigException
     */
	public function __construct() {
		parent::__construct( "MWUnit", "mwunit-runtests", true );
		parent::requireLogin();

		set_time_limit( $this->getConfig()->get( 'MWUnitMaxTestExecutionTime' ) );
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return MWUnit::isRunning() ?
			$this->msg( 'mwunit-special-result-title' )->plain() :
			$this->msg( 'mwunit-special-title' )->plain();
	}

	/**
	 * Returns part of the message key for the category to which this special page belongs.
	 *
	 * @return string
	 */
	public function getGroupName() {
		return 'mwunit';
	}

	/**
	 * @param string|null $subpage
	 * @throws \MWException
	 */
	public function execute( $subpage ) {
		$this->checkPermissions();

		if ( $this->shouldRunTests() && $this->runTests() ) {
			$nav = $this->msg( 'parentheses' )
						->rawParams( $this->getLanguage()->pipeList( [
							$this->getLinkRenderer()->makeLink(
								\Title::newFromText( "Special:MWUnit" ),
								new \HtmlArmor( wfMessage( 'mwunit-nav-home' )->plain() )
							)
						] ) )->text();
			$nav = $this->msg( 'mwunit-nav-introtext' ) . $nav;
			$nav = \Xml::tags( 'div', [ 'class' => 'mw-mwunit-special-navigation' ], $nav );
			$this->getOutput()->setSubtitle( $nav );

			$test_results = $this->runner->getTestRunStore();
			$this->renderTestResults( $test_results );
		} else {
		    try {
                $this->showForms();
            } catch( RebuildRequiredException $e ) {
		        $this->getOutput()->showFatalError( $e->getMessage() );
            }
		}

		$this->setHeaders();
	}

	private function runTests(): bool {
		if ( $this->getRequest()->getVal( 'unitTestGroup' ) !== null ) {
			// Run all tests in the given unit test group
			$group = $this->getRequest()->getVal( 'unitTestGroup' );

			try {
				$test_suite = TestSuite::newFromGroup( $group );
			} catch ( MWUnitException $e ) {
				return false;
			}
		} elseif ( $this->getRequest()->getVal( 'unitTestIndividual' ) !== null ) {
			// Run the specified individual test
			if ( strpos( $this->getRequest()->getVal( 'unitTestIndividual' ), '::' ) === false ) {
			    return false;
			}

			$test_name = $this->getRequest()->getVal( 'unitTestIndividual' );

			try {
                $test_suite = TestSuite::newFromText( $test_name );
            } catch( MWUnitException $e ) {
			    return false;
            }
		} elseif ( $this->getRequest()->getVal( 'unitTestCoverTemplate' ) ) {
            $covers = $this->getRequest()->getVal( 'unitTestCoverTemplate' );

			try {
				$test_suite = TestSuite::newFromCovers( $covers );
			} catch ( MWUnitException $e ) {
				return false;
			}
		} else {
			// Run the specified page
			$title = \Title::newFromText( $this->getRequest()->getVal( 'unitTestPage' ) );

			if ( !$title instanceof \Title || !$title->exists() ) {
			    return false;
			}

			if ( $title->getNamespace() !== NS_TEST ) {
			    return false;
			}

			try {
				$test_suite = TestSuite::newFromTitle( $title );
			} catch ( MWUnitException $e ) {
				return false;
			}
		}

		if ( count( $test_suite ) === 0 ) {
			return false;
		}

		$this->runner = new TestSuiteRunner( $test_suite, null );

        try {
            $result = $this->runner->run();
        } catch (MWUnitException $e) {
            return false;
        }

        if ( $result === false ) {
            return false;
        }

        return true;
	}

    /**
     * @return void
     * @throws RebuildRequiredException
     * @throws \MWException
     */
	private function showForms() {
		$this->getOutput()->addHTML( $this->renderRunGroupTestsForm() );
		$this->getOutput()->addHTML( $this->renderRunIndividualTestForm() );
	}

	/**
	 * Renders the form for running a group of tests.
	 * @throws \MWException
	 * @return string
	 */
	private function renderRunGroupTestsForm() {
		$group_test_run_form_descriptor = [
			'test_group' => [
				'name' => 'unitTestGroup',
				'type' => 'select',
				'label-message' => 'mwunit-special-group-label',
				'options' => $this->getTestGroupsDescriptor()
			]
		];

		$form = \HTMLForm::factory( 'ooui', $group_test_run_form_descriptor, $this->getContext() );

		$form->setMethod( 'get' );
		$form->setSubmitTextMsg( 'mwunit-run-tests-button' );
		$form->setWrapperLegendMsg( 'mwunit-group-test-legend' );
		$form->setFormIdentifier( 'group-test-run-form' );

		return $form->prepareForm()->getHTML( false );
	}

	/**
	 * Renders the form for running an individual test.
	 * @return string
	 *@throws \MWException|RebuildRequiredException
	 */
	private function renderRunIndividualTestForm() {
		$group_test_run_form_descriptor = [
			'test_individual' => [
				'name' => 'unitTestIndividual',
				'type' => 'select',
				'label-message' => 'mwunit-special-individual-label',
				'options' => $this->getIndividualTestDescriptor()
			]
		];

		$form = \HTMLForm::factory( 'ooui', $group_test_run_form_descriptor, $this->getContext() );

		$form->setMethod( 'get' );
		$form->setSubmitTextMsg( 'mwunit-run-test-button' );
		$form->setWrapperLegendMsg( 'mwunit-individual-test-legend' );
		$form->setFormIdentifier( 'individual-test-run-form' );

		return $form->prepareForm()->getHTML( false );
	}

	/**
	 * @return array
	 */
	private function getTestGroupsDescriptor(): array {
		$database = wfGetDb( DB_REPLICA );
		$result = $database->select(
			'mwunit_tests',
			'test_group',
			[],
			'Database::select',
			'DISTINCT'
		);

		$row_count = $result->numRows();

		$descriptor = [];
		for ( $i = 0; $i < $row_count; $i++ ) {
			$row = $result->current();
			$descriptor[ $row->test_group ] = $row->test_group;

			$result->next();
		}

		return $descriptor;
	}

    /**
     * @return array
     * @throws RebuildRequiredException
     */
	private function getIndividualTestDescriptor(): array {
		$database = wfGetDb( DB_REPLICA );
		$result = $database->select(
			'mwunit_tests',
			[ 'article_id', 'test_name' ],
			[],
			'Database::select',
			'DISTINCT'
		);

		$row_count = $result->numRows();

		$descriptor = [];
		for ( $i = 0; $i < $row_count; $i++ ) {
			$row = $result->current();

			$title = \Title::newFromID( $row->article_id );

			if ( $title === null ) {
			    throw new RebuildRequiredException( 'mwunit-rebuild-required' );
            }

			$test_identifier = $title->getText() . "::" . $row->test_name;
			$descriptor[ $test_identifier ] = $test_identifier;
			$result->next();
		}

		return $descriptor;
	}

	/**
	 * Renders the given test results for usage on the Special Page in the test result report.
	 *
	 * @param TestRunStore $test_run_store
	 */
	private function renderTestResults( TestRunStore $test_run_store ) {
		if ( count( $test_run_store->getAll() ) === 0 ) {
			$this->getOutput()->showErrorPage( 'mwunit-generic-error-title', 'mwunit-generic-error-description' );
			return;
		}

		$test_count      = $this->runner->getTestCount();
		$assertion_count = $this->runner->getTotalAssertionsCount();
		$failures_count  = $this->runner->getNotPassedCount();

		$this->getOutput()->addHTML(
			"<p>" . wfMessage( 'mwunit-test-result-intro' )->plain() . "</p>" .
			"<p><b>" . wfMessage(
				'mwunit-test-result-summary',
				$test_count,
				$assertion_count,
				$failures_count
			)->plain() . "</b></p>"
		);

		foreach ( $test_run_store as $test_run ) {
			$this->getOutput()->addHTML( $this->renderTest( $test_run ) );
		}
	}

    /**
     * Renders the given TestResult object.
     *
     * @param TestRun $run
     * @return string
     */
	private function renderTest( TestRun $run ): string {
        // TODO: Factor this function into multiple function that print a type of test.

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

    private function renderRiskyTest( TestRun $run ) {
        return sprintf(
            '<div class="warningbox" style="display:block;">' .
            '<p><span style="color:#fc3"><b>%s</b></span> %s</p>%s' .
            '</div>',
            $this->msg( 'mwunit-test-risky' ),
            $this->renderTestHeader( $run->getResult() ),
            $this->renderSummary( $run )
        );
    }

    private function renderFailedTest( TestRun $run ) {
        return sprintf(
            '<div class="errorbox" style="display:block;">' .
            '<p><span style="color:#d33"><b>%s</b></span> %s</p>%s' .
            '</div>',
            $this->msg( 'mwunit-test-failed' ),
            $this->renderTestHeader( $run->getResult() ),
            $this->renderSummary( $run )
        );
    }

    private function renderSucceededTest( TestRun $run ) {
        return sprintf(
            '<div class="successbox" style="display:block;">' .
            '<p><span style="color:#14866d"><b>%s</b></span> %s</p>%s' .
            '</div>',
            $this->msg( 'mwunit-test-success' ),
            $this->renderTestHeader( $run->getResult() ),
            $this->renderSummary( $run )
        );
    }

    /**
     * Renders the header of a test result.
     *
     * @param TestResult $result
     * @return string|null
     * @throws RebuildRequiredException
     */
	private function renderTestHeader( TestResult $result ) {
		$page_name = $result->getPageName();
		$test_name = $result->getTestName();

		$test_title = MWUnit::testNameToSentence( $test_name );

		$title = \Title::newFromText( $page_name );

        if ( $title === null ) {
            throw new RebuildRequiredException( 'mwunit-rebuild-required' );
        }

		$test_url = $title->getLinkURL();

		return sprintf(
			"%s (<code><a href='%s' title='%s'>%s</a></code>)",
			$test_title,
			$test_url,
			$page_name,
			$result->getTestCase()
		);
	}

    private function renderSummary( TestRun $run ): string {
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

    private function formatTestOutput(TestOutputStore $collector ) {
	    return count( $collector->getAll() ) === 0 ?
            '' :
            implode( "\n", $collector->getAll() );
    }

	/**
	 * Returns true if and only if tests need to be ran. This is the case if and only if
	 * either the GET variable 'unitTestGroup', 'unitTestPage' or 'unitTestIndividual' is set and is not empty.
	 *
	 * @return bool
	 */
	private function shouldRunTests(): bool {
		return $this->getRequest()->getVal( 'unitTestGroup' ) !== null &&
				!empty( $this->getRequest()->getVal( 'unitTestGroup' ) )
			|| $this->getRequest()->getVal( 'unitTestIndividual' ) !== null &&
				!empty( $this->getRequest()->getVal( 'unitTestIndividual' ) )
			|| $this->getRequest()->getVal( 'unitTestPage' ) !== null &&
				!empty( $this->getRequest()->getVal( 'unitTestPage' ) )
			|| $this->getRequest()->getVal( 'unitTestCoverTemplate' ) &&
				!empty( $this->getRequest()->getVal( 'unitTestCoverTemplate' ) );
	}
}
