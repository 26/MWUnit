<?php

namespace MWUnit\Tests\Integration;

use MediaWikiTestCase;
use MWUnit\ParserData;

class ParserDataTest extends MediaWikiTestCase {
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $parser_mock;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $frame_mock;

	/**
	 * @var string[]
	 */
	private $sample_data;

	/**
	 * @var string
	 */
	private $error_reporting;

	public function setUp(): void {
		parent::setUp();

		$this->error_reporting = ini_get( 'error_reporting' );

		$this->parser_mock = $this->createMock( \Parser::class );
		$this->frame_mock = $this->createMock( \PPFrame::class );
		$this->frame_mock->method( "expand" )
			->will( $this->returnArgument( 0 ) );
		$this->sample_data = array_map( function (): string {
			return md5( rand() );
		}, array_fill( 0, 100, null ) );
	}

	public function tearDown(): void {
		parent::tearDown();

		// Clean error reporting
		ini_set( 'error_reporting', $this->error_reporting );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ParserData::class,
			new ParserData( $this->parser_mock, $this->frame_mock, $this->sample_data )
		);
	}

	public function testSetInput() {
		$sample_input = md5( rand() );

		$instance = $this->getInstance();
		$instance->setInput( $sample_input );

		$this->assertEquals( $sample_input, $instance->getInput() );
	}

	public function testGetFlags() {
		$sample_flag = 0b001 | 0b100;

		$instance = $this->getInstance();
		$instance->setFlags( $sample_flag );

		$this->assertEquals( $sample_flag, $instance->getFlags() );
	}

	public function testGetParser() {
		$instance = $this->getInstance();
		$this->assertSame( $this->parser_mock, $instance->getParser() );
	}

	public function testGetArguments() {
		$instance = $this->getInstance();
		$sample_data = array_map( "trim", $this->sample_data );
		$this->assertArrayEquals( $sample_data, $instance->getArguments() );
	}

	public function testGetArgument() {
		$instance = $this->getInstance();
		$sample_data = array_map( "trim", $this->sample_data );

		for ( $i = 0; $i < count( $sample_data ); $i++ ) {
			$this->assertSame( $sample_data[$i], $instance->getArgument( $i ) );
		}
	}

	public function testCount() {
		$instance = $this->getInstance();
		$actual = count( $this->sample_data );

		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertSame( $actual, $instance->count() );
		}
	}

	public function testIterator() {
		$instance = $this->getInstance();
		$sample_data = array_map( "trim", $this->sample_data );

		foreach ( $instance as $key => $item ) {
			$this->assertSame( $sample_data[$key], $item );
		}
	}

	public function getInstance() {
		return new ParserData( $this->parser_mock, $this->frame_mock, $this->sample_data );
	}
}
