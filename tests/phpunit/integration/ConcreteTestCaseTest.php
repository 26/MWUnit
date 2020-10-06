<?php


namespace MWUnit\Tests\Integration;

use MediaWikiTestCase;
use MWUnit\ConcreteTestCase;

class ConcreteTestCaseTest extends MediaWikiTestCase {
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $title_mock;

    public function setUp(): void {
        parent::setUp();

        $this->setMwGlobals( 'wgMWUnitForceCoversAnnotation', false );
        $this->title_mock = $this->createMock(\Title::class);
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testNewFromTagWithEmptyArguments() {
        $tag_input = "Lorem ipsum dolor et";
        $tag_arguments = [];

        $parser_mock = $this->createMock(\Parser::class);
        $parser_mock->method('getTitle')
            ->willReturn($this->title_mock);

        $this->assertFalse( ConcreteTestCase::newFromTag($tag_input, $tag_arguments, $parser_mock) );
    }

    public function testNewFromTagMissingName() {
        $tag_input = "Lorem ipsum dolor et";
        $tag_arguments = [
            "group" => "Foobar"
        ];

        $parser_mock = $this->createMock(\Parser::class);
        $parser_mock->method('getTitle')
            ->willReturn($this->title_mock);

        $this->assertFalse( ConcreteTestCase::newFromTag($tag_input, $tag_arguments, $parser_mock) );
    }

    public function testNewFromTagMissingGroup() {
        $tag_input = "Lorem ipsum dolor et";
        $tag_arguments = [
            "name" => "Foobar"
        ];

        $parser_mock = $this->createMock(\Parser::class);
        $parser_mock->method('getTitle')
            ->willReturn($this->title_mock);

        $this->assertFalse( ConcreteTestCase::newFromTag($tag_input, $tag_arguments, $parser_mock) );
    }

    public function testNewFromTagValid() {
        $tag_input = "Lorem ipsum dolor et";
        $tag_arguments = [
            "name" => "Foobar",
            "group" => "Boofar"
        ];

        $parser_mock = $this->createMock(\Parser::class);
        $parser_mock->method('getTitle')
            ->willReturn($this->title_mock);

        $this->assertNotFalse( ConcreteTestCase::newFromTag($tag_input, $tag_arguments, $parser_mock) );
    }

    public function testNewFromTagForceCoversAnnotation() {
        $tag_input = "Lorem ipsum dolor et";
        $tag_arguments = [
            "name" => "Foobar",
            "group" => "Boofar"
        ];

        $parser_mock = $this->createMock(\Parser::class);
        $parser_mock->method('getTitle')
            ->willReturn($this->title_mock);

        $this->setMwGlobals( 'wgMWUnitForceCoversAnnotation', true );
        $this->assertFalse( ConcreteTestCase::newFromTag($tag_input, $tag_arguments, $parser_mock) );

        $this->setMwGlobals( 'wgMWUnitForceCoversAnnotation', false );
        $this->assertNotFalse( ConcreteTestCase::newFromTag($tag_input, $tag_arguments, $parser_mock) );
    }

    public function testGetName() {
        $sample_name = "foobar";
        $instance = $this->getInstance( $sample_name, "boofar" );

        $this->assertSame($sample_name, $instance->getName());
    }

    public function testGetGroup() {
        $sample_group = "foobar";
        $instance = $this->getInstance( "boofar", $sample_group );

        $this->assertSame($sample_group, $instance->getGroup());
    }

    public function testGetOptions() {
        $sample_name = "foobar";
        $sample_group = "boofar";
        $sample_arguments = [
            "foo" => "bar"
        ];

        $instance = $this->getInstance($sample_name, $sample_group, "Lorem ipsum", $sample_arguments);

        $this->assertArrayEquals($sample_arguments, $instance->getOptions());
    }

    public function testGetOption() {
        $sample_name = "foobar";
        $sample_group = "boofar";

        // Create a array with random keys and unique values
        $sample_arguments = array_flip(
            array_map(function(): string {
                return md5(rand());
            }, array_fill(0, 100, null))
        );

        $instance = $this->getInstance($sample_name, $sample_group, "Lorem ipsum", $sample_arguments);

        foreach( $sample_arguments as $key => $value ) {
            $this->assertSame( $value, $instance->getOption( $key ) );
        }
    }

    public function getInstance( string $name, string $group, string $input = "Lorem ipsum dolor et", array $arguments = [] ) {
        $tag_arguments = [
            "name" => $name,
            "group" => $group
        ];

        $tag_arguments += $arguments;

        $parser_mock = $this->createMock(\Parser::class);
        $parser_mock->method('getTitle')
            ->willReturn($this->title_mock);

        return ConcreteTestCase::newFromTag($input, $tag_arguments, $parser_mock);
    }
}