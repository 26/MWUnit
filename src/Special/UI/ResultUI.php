<?php

namespace MWUnit\Special\UI;

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use MWUnit\MWUnit;
use MWUnit\Profiler;
use MWUnit\Renderer\Document;
use MWUnit\Renderer\Tag;
use MWUnit\Runner\Result\TestResult;
use MWUnit\Runner\TestRun;
use MWUnit\Runner\TestSuiteRunner;
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
		$this->getOutput()->addModuleStyles( "ext.mwunit.TestPage.css" );

		$test_count      = $this->runner->getTestCount();
		$assertion_count = $this->runner->getTotalAssertionsCount();

		$risky_count = $this->runner->getRiskyCount();
		$failure_count = $this->runner->getFailedCount();
		$skipped_count = $this->runner->getSkippedCount();

		$profiler = Profiler::getInstance();

		// In milliseconds
		$execution_time = floor( $profiler->getTotalExecutionTime() * 1000 );

		// In megabytes
		$memory_usage = floor( $profiler->getPeakMemoryUse() / 1024 / 1024 );

		$summary = wfMessage(
			'mwunit-test-result-summary',
			$test_count,
			$assertion_count,
			$risky_count,
			$failure_count,
			$skipped_count
		)->plain();

		if ( MediaWikiServices::getInstance()->getMainConfig()->get( "MWUnitShowProfilingInfo" ) ) {
			$summary .= " " . wfMessage(
				'mwunit-test-result-summary-profiling-info',
				$execution_time,
				$memory_usage
			)->plain();
		}

		$this->getOutput()->addHTML(
			( new Tag( "p", new Tag( "b", $summary ) ) )->__toString()
		);

		$store = $this->runner->getTestRunStore();

		if ( count( $store ) === 0 ) {
			$no_results = new Tag( "div", wfMessage( "mwunit-no-results" )->plain(), [ "class" => "mwunit-message-box" ] );
			$this->getOutput()->addHTML( $no_results );
		} else {
			foreach ( $store as $test_run ) {
				$this->getOutput()->addHTML( $this->renderTest( $test_run ) );
			}
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
			wfMessage( 'mwunit-nav-home' )->plain() => "Special:UnitTests"
		];
	}

	/**
	 * Renders the given TestResult object.
	 *
	 * @param TestRun $run
	 * @return string
	 * @throws \Exception
	 */
	private function renderTest( TestRun $run ): string {
		switch ( $run->getResult()->getResultConstant() ) {
			case TestResult::T_RISKY:
				return $this->renderRiskyTest( $run )->__toString();
			case TestResult::T_FAILED:
				return $this->renderFailedTest( $run )->__toString();
			case TestResult::T_SUCCESS:
				return $this->renderSucceededTest( $run )->__toString();
			case TestResult::T_SKIPPED:
				return $this->renderSkippedTest( $run )->__toString();
		}

		throw new \Exception( "Invalid result constant" );
	}

	/**
	 * Renders a risky test.
	 *
	 * @param TestRun $run
	 * @return Tag
	 */
	private function renderRiskyTest( TestRun $run ): Tag {
		return $this->renderTestBox(
			$run,
			"warningbox",
			"#fc3",
			"mwunit-test-risky"
		);
	}

	/**
	 * Renders a failed test.
	 *
	 * @param TestRun $run
	 * @return Tag
	 */
	private function renderFailedTest( TestRun $run ): Tag {
		return $this->renderTestBox(
			$run,
			"errorbox",
			"#d33",
			"mwunit-test-failed"
		);
	}

	/**
	 * Renders a succeeded test.
	 *
	 * @param TestRun $run
	 * @return Tag
	 */
	private function renderSucceededTest( TestRun $run ): Tag {
		return $this->renderTestBox(
			$run,
			"successbox",
			"#14866d",
			"mwunit-test-success"
		);
	}

	/**
	 * Renders a risky test.
	 *
	 * @param TestRun $run
	 * @return Tag
	 */
	private function renderSkippedTest( TestRun $run ): Tag {
		return $this->renderTestBox(
			$run,
			"warningbox",
			"#ff8c00",
			"mwunit-test-skipped"
		);
	}

	/**
	 * Renders a generic test result as an HTML box.
	 *
	 * @param TestRun $run
	 * @param string $box_class Class to use for the box (i.e. errorbox, successbox, ed...)
	 * @param string $title_color Color to use for the title text (as hex)
	 * @param string $title_msg Message key to use for the title prefix
	 * @return Tag
	 */
	private function renderTestBox( TestRun $run, string $box_class, string $title_color, string $title_msg ): Tag {
		return new Tag(
			"div",
			new Document( [
				new Tag(
					"p",
					new Document( [
						new Tag(
							"span",
							new Tag(
								"b",
								wfMessage( $title_msg )->plain() . " "
							),
							[ "style" => "color: $title_color" ]
						),
						new Tag(
							"span",
							$this->formatTestHeader( $run )
						)
					] )
				),
				new Tag(
					"span",
					$this->formatSummary( $run )
				)
			] ),
			[ "class" => $box_class, "style" => "display: block" ]
		);
	}

	/**
	 * Formats the header for the given TestResult object.
	 *
	 * @param TestRun $test_run
	 * @return Document
	 */
	private function formatTestHeader( TestRun $test_run ): Document {
		$result = $test_run->getResult();

		$test_name = $result->getTestCase()->getTestName();
		$page_name = $result->getTestCase()->getTestPage()->getFullText();

		$title = \Title::newFromText( $page_name );
		$link_href = $title->getLinkURL();
		$link_title = $result->getTestCase()->getCanonicalName();
		$link = new Tag( "a", $link_title, [ "href" => $link_href, "title" => $link_title ] );

		try {
			$show_profiling_info = MediaWikiServices::getInstance()->getMainConfig()->get( "MWUnitShowProfilingInfo" );
		} catch ( \ConfigException $e ) {
			$show_profiling_info = false;
		}

		if ( $show_profiling_info ) {
			$profiling_info = [
				new Tag( "span", " (" ),
				new Tag( "code", floor( $test_run->getExecutionTime() * 1000 ) . "ms" ),
				new Tag( "span", ")" )
			];
		} else {
			$profiling_info = [];
		}

		$tags = array_merge( [
			new Tag( "span", MWUnit::testNameToSentence( $test_name ) . " (" ),
			new Tag( "code", $link ),
			new Tag( "span", ")" )
		], $profiling_info );

		return new Document( $tags );
	}

	/**
	 * Formats the summary for the given TestRun object.
	 *
	 * @param TestRun $run
	 * @return Document
	 */
	private function formatSummary( TestRun $run ): Document {
		$message = $run->getResult()->getMessage();
		$test_outputs = $run->getTestOutputs();
		$test_output = $this->formatTestOutput( $test_outputs );

		if ( !$message && !$test_output ) {
			return new Document( [] );
		}

		if ( !$message && $test_output ) {
			return new Document( [
				new Tag( "hr", "" ),
				new Tag( "pre", "The test outputted the following:\n\n$test_output" )
			] );
		}

		if ( $test_output ) {
			$message .= "\n\nIn addition, the test outputted the following:\n\n$test_output";
		}

		return new Document( [
			new Tag( "hr", "" ),
			new Tag( "pre", $message )
		] );
	}

	/**
	 * Formats the given TestOutputStore as a string.
	 *
	 * @param string[] $test_outputs
	 * @return string
	 */
	private function formatTestOutput( array $test_outputs ): string {
		return count( $test_outputs ) === 0 ?
			'' :
			implode( "\n", $test_outputs );
	}

	/**
	 * @inheritDoc
	 */
	public function getClass(): string {
		return self::class;
	}
}
