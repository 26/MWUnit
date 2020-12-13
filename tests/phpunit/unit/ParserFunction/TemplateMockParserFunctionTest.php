<?php

namespace MWUnit\Tests\Unit\ParserFunction;

use MWUnit\ParserFunction\TemplateMockParserFunction;
use MWUnit\Runner\TestRun;
use MWUnit\TemplateMockStore;
use MWUnit\TestCase;
use Parser;
use Revision;

class TemplateMockParserFunctionTest extends \MediaWikiTestCase {
	/**
	 * @var TemplateMockStore|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $template_mock_store_mock;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\Title
	 */
	private $title_mock;

	/**
	 * @var TestRun|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $test_run_mock;

	/**
	 * @var Parser|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $parser_mock;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|Revision
	 */
	private $revision_mock;

	public function setUp(): void {
		parent::setUp();

		$this->template_mock_store_mock = $this->createMock( TemplateMockStore::class );
		$this->title_mock = $this->createMock( \Title::class );
		$this->test_run_mock = $this->createMock( TestRun::class );

		// Create some mocks the signature required but that are not used in the function
		$this->parser_mock = $this->createMock( Parser::class );
		$this->revision_mock = $this->createMock( Revision::class );
	}

	public function testTextSetOnParserFetchTemplate() {
		$title = $this->title_mock;

		// Set the Title object in the NS_TEMPLATE namespace
		$title->method( "getNamespace" )->willReturn( NS_TEMPLATE );

		// Set the name of the Title object to "Foobar"
		$title->method( "getText" )->willReturn( "Foobar" );

		$store = $this->template_mock_store_mock;

		// Set the store such that page names are ignored (all pages are mocked and have the content "foo")
		$store->method( "exists" )->willReturn( true );
		$store->method( "get" )->willReturn( "foo" );

		// The injected test case does not have a covers annotation
		$test_case_mock = $this->createMock( TestCase::class );
		$test_case_mock->method( "getCovers" )->willReturn( null );

		$test_run = $this->test_run_mock;

		// The test run should return our mock
		$test_run->method( "getTestCase" )->willReturn( $test_case_mock );

		TemplateMockParserFunction::setTemplateMockStore( $store );
		TemplateMockParserFunction::setTestRun( $this->test_run_mock );

		$text = null;
		$deps = [];

		TemplateMockParserFunction::onParserFetchTemplate( $this->parser_mock, $title, $this->revision_mock, $text, $deps );

		// "$text" should now have our "template mock" page's content
		$this->assertSame( "foo", $text );
	}

	public function testNothingWithoutTestRun() {
		TemplateMockParserFunction::setTestRun( $this->test_run_mock );

		$text = null;
		$deps = [];

		TemplateMockParserFunction::onParserFetchTemplate( $this->parser_mock, $this->title_mock, $this->revision_mock, $text, $deps );

		// Assert the $text is still the value it was (null)
		$this->assertNull( $text );
	}

	public function testNothingWithoutMock() {
		$store = $this->template_mock_store_mock;

		// Create an empty store
		$store->method( "exists" )->willReturn( false );

		TemplateMockParserFunction::setTemplateMockStore( $store );

		$text = null;
		$deps = [];

		TemplateMockParserFunction::onParserFetchTemplate( $this->parser_mock, $this->title_mock, $this->revision_mock, $text, $deps );

		// Assert the $text is still the value it was (null)
		$this->assertNull( $text );
	}

	public function testRiskyWhenCoverMocked() {
		$store = $this->template_mock_store_mock;

		// Set the store such that page names are ignored (all pages are mocked and have the content "foo")
		$store->method( "exists" )->willReturn( true );
		$store->method( "get" )->willReturn( "foo" );

		// Create the Title object for page "Test:Foobar"
		$title_mock = $this->title_mock;
		$title_mock->method( "getText" )->willReturn( "Foobar" );
		$title_mock->method( "getNamespace" )->willReturn( NS_TEMPLATE );

		// Set "Test:Foobar" as the covers for the TestCase
		$test_case_mock = $this->createMock( TestCase::class );
		$test_case_mock->method( "getCovers" )->willReturn( "Foobar" );

		// Inject our "TestCase" in the TestRun mock
		$test_run_mock = $this->test_run_mock;
		$test_run_mock->method( "getTestCase" )->willReturn( $test_case_mock );

		// Assert the "setRisky" method is called at least once (since its cover gets mocked, it must be marked risky)
		$test_run_mock->expects( $this->once() )->method( "setRisky" );

		$text = null;
		$deps = [];

		// Inject our mocks
		TemplateMockParserFunction::setTemplateMockStore( $store );
		TemplateMockParserFunction::setTestRun( $test_run_mock );

		// Simulate "onParserFetchTemplate"
		TemplateMockParserFunction::onParserFetchTemplate( $this->parser_mock, $title_mock, $this->revision_mock, $text, $deps );
	}
}
