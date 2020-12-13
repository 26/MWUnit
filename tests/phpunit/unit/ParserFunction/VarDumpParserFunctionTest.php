<?php

namespace MWUnit\Tests\Unit\ParserFunction;

use MediaWikiTestCase;
use MWUnit\ParserData;
use MWUnit\ParserFunction\VarDumpParserFunction;
use MWUnit\Runner\TestRun;

class VarDumpParserFunctionTest extends MediaWikiTestCase {
	/**
	 * @var VarDumpParserFunction
	 */
	private $var_dump_pf;

	/**
	 * @var ParserData|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $parser_data;

	/**
	 * @var TestRun|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $test_run;

	public function setUp(): void {
		parent::setUp();

		$this->var_dump_pf = new VarDumpParserFunction();

		$this->test_run = $this->createMock( TestRun::class );
		$this->test_run->test_outputs = [];

		$this->parser_data = $this->createMock( ParserData::class );
	}

	/**
	 * Tests if nothing happens when no TestRun object is given to the VarDumpParserFunction class.
	 */
	public function testNothingWithoutTestRun() {
		$this->var_dump_pf->execute( $this->parser_data );

		$this->assertEmpty( $this->test_run->test_outputs );
	}

	/**
	 * Tests whether the correct value is actually added to the TestRun object.
	 */
	public function testExecute() {
		$get_argument_will_return = "foobar";

		$this->var_dump_pf::setTestRun( $this->test_run );
		$this->parser_data->method( "getArgument" )->willReturn( $get_argument_will_return );
		$this->var_dump_pf->execute( $this->parser_data );

		$this->assertSame(
			$this->var_dump_pf->formatDump( $get_argument_will_return ),
			$this->test_run->test_outputs[0]
		);
	}

	/**
	 * @dataProvider formatDumpProvider
	 * @param string $input
	 * @param string $expected
	 */
	public function testFormatDump( string $input, string $expected ) {
		$this->assertSame( $expected, $this->var_dump_pf->formatDump( $input ) );
	}

	public function formatDumpProvider() {
		return [
			[
				"1.28",
				"float(1.28)"
			],
			[
				"1,28",
				"float(1.28)"
			],
			[
				"12",
				"int(12)"
			],
			[
				"0056",
				"int(56)"
			],
			[
				"1000.00000",
				"float(1000.00000)"
			],
			[
				"foobar",
				"string(6) \"foobar\""
			],
			[
				"    foobar    ",
				"string(14) \"    foobar    \""
			]
		];
	}
}
