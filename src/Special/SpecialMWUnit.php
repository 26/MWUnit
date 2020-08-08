<?php

namespace MWUnit\Special;

use MWUnit\Exception\MWUnitException;
use MWUnit\MWUnit;
use MWUnit\Registry\TestCaseRegistry;
use MWUnit\Runner\Result\RiskyTestResult;
use MWUnit\Runner\Result\SuccessTestResult;
use MWUnit\Runner\Result\TestResult;
use MWUnit\Runner\TestSuiteRunner;
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

			$test_results = $this->runner->getResults();
			$this->renderTestResults( $test_results );
		} else {
			$this->showForms();
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
	 * @throws \MWException
	 * @return void
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
	 * @throws \MWException
	 * @return string
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
			$test_identifier = \Title::newFromID( $row->article_id )->getText() . "::" . $row->test_name;
			$descriptor[ $test_identifier ] = $test_identifier;
			$result->next();
		}

		return $descriptor;
	}

	/**
	 * Renders the given test results for usage on the Special Page in the test result report.
	 *
	 * @param array $test_results
	 */
	private function renderTestResults( array $test_results ) {
		if ( count( $test_results ) === 0 ) {
			$this->getOutput()->showErrorPage( 'mwunit-generic-error-title', 'mwunit-generic-error-description' );
			return;
		}

		$test_count = $this->runner->getTestCount();
		$assertion_count = $this->runner->getTotalAssertionsCount();
		$failures_count = $this->runner->getNotPassedCount();

		$this->getOutput()->addHTML(
			"<p>" . wfMessage( 'mwunit-test-result-intro' )->plain() . "</p>" .
			"<p><b>" . wfMessage(
				'mwunit-test-result-summary',
				$test_count,
				$assertion_count,
				$failures_count
			)->plain() . "</b></p>"
		);

		foreach ( $test_results as $test_result ) {
			$this->getOutput()->addHTML( $this->renderTestResult( $test_result ) );
		}
	}

	/**
	 * Renders the given TestResult object.
	 *
	 * @param TestResult $result
	 * @return string
	 */
	private function renderTestResult( TestResult $result ): string {
		if ( $result->getResult() === TestResult::T_RISKY ) {
			$summary = $result->getMessage();
			$summary_formatted = $summary === null ? $summary : "<hr/><pre>$summary</pre>";

			return sprintf(
				'<div class="warningbox" style="display:block;">' .
							'<p><span style="color:#fc3"><b>%s</b></span> %s</p>%s' .
						'</div>',
				$this->msg( 'mwunit-test-risky' ),
				$this->renderTestHeader( $result ),
				$summary_formatted
			);
		}

		if ( $result->getResult() === TestResult::T_SUCCESS ) {
			return sprintf(
				'<div class="successbox" style="display:block;">' .
							'<p><span style="color:#14866d"><b>%s</b></span> %s</p>' .
						'</div>',
				$this->msg( 'mwunit-test-success' ),
				$this->renderTestHeader( $result )
			);
		}

		if ( $result->getResult() === TestResult::T_FAILED ) {
			return sprintf(
				'<div class="errorbox" style="display:block;"><p><span style="color:#d33"><b>%s</b></span> %s</p>' .
						'<hr/><pre>%s</pre></div>',
				$this->msg( 'mwunit-test-failed' ),
				$this->renderTestHeader( $result ),
				htmlspecialchars( $result->getMessage() )
			);
		}

		return '';
	}

	/**
	 * Renders the header of a test result.
	 *
	 * @param TestResult $result
	 * @return string|null
	 */
	private function renderTestHeader( TestResult $result ) {
		$page_name = $result->getPageName();
		$test_name = $result->getTestName();

		$test_title = MWUnit::testNameToSentence( $test_name );

		$title = \Title::newFromText( $page_name );

		if ( $title === null || $title === false ) {
			return null;
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
