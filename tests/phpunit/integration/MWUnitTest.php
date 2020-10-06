<?php


namespace MWUnit\Tests\Integration;

use MWUnit\MWUnit;

class MWUnitTest extends \MediaWikiTestCase {
    public function testOnMovePageIsValidMoveWithTestToTestContentModel() {
        $old_title_mock = $this->getTitleMock( NS_TEST, CONTENT_MODEL_TEST );
        $new_title_mock = $this->getTitleMock( NS_TEST, CONTENT_MODEL_TEST );
        $status_mock = $this->getNonFatalStatusMock();

        MWUnit::onMovePageIsValidMove($old_title_mock, $new_title_mock, $status_mock);
    }

    public function testMoveWithTestToNotTestContentModel() {
        $old_title_mock = $this->getTitleMock(NS_TEST, CONTENT_MODEL_TEST);
        $new_title_mock = $this->getTitleMock(NS_TEST, CONTENT_MODEL_TEXT);
        $status_mock = $this->getFatalStatusMock();

        MWUnit::onMovePageIsValidMove($old_title_mock, $new_title_mock, $status_mock);
    }

    public function testMoveTestContentModelOutsideTestNamespace() {
        $old_title_mock = $this->getTitleMock(NS_TEST, CONTENT_MODEL_TEST);
        $new_title_mock = $this->getTitleMock(NS_MAIN, CONTENT_MODEL_TEST);
        $status_mock = $this->getFatalStatusMock();

        MWUnit::onMovePageIsValidMove($old_title_mock, $new_title_mock, $status_mock);
    }

    public function testTestNameToSentence() {
        $samples = [
            "testRemovesTest" => "Removes Test",
            "doTestIgnoreFirstCase" => "Do Test Ignore First Case",
            "test_works_with_camel" => "Works With Camel",
            "works_with_camel" => "Works With Camel",
            "testworks" => "Testworks",
            "AAAA" => "A A A A",
            "__" => "",
            "" => ""
        ];

        foreach ( $samples as $input => $expected ) {
            $this->assertSame( $expected, MWUnit::testNameToSentence( $input ) );
        }
    }

    public function testOnContentHandlerDefaultModelFor() {
        $title_mock = $this->getTitleMock( NS_TEST, CONTENT_MODEL_TEXT );
        $model = null;

        $this->assertFalse( MWUnit::onContentHandlerDefaultModelFor( $title_mock, $model ) );
        $this->assertSame( CONTENT_MODEL_TEST, $model );

        $title_mock = $this->getTitleMock( NS_MAIN, CONTENT_MODEL_TEXT );
        $model = null;

        $this->assertTrue( MWUnit::onContentHandlerDefaultModelFor( $title_mock, $model ) );
        $this->assertNull( $model );
    }

    public function testAreAttributesValid() {
        $valid = [
            [
                "name" => "foobar",
                "group" => "boofar"
            ],
            [
                "name" => "foobar",
                "group" => "boofar",
                "covers" => "foobar"
            ],
            [
                "name" => "foobarfoobarfoobarfoobarfoobarfoobarfoobarfoobarfoobarfoobarfoobarfoobarfoobarfoobar",
                "group" => "boofar",
                "covers" => "foobar"
            ]
        ];

        $invalid = [
            [
                "name" => str_repeat("f", 256),
                "group" => "boofar"
            ],
            [
                "name" => "foobar",
                "group" => str_repeat("f", 256)
            ],
            [
                "name" => str_repeat("f", 256),
                "group" => str_repeat("f", 256)
            ],
            [
                "name" => "foobar",
                "group" => "foo%bar"
            ],
            [
                "name" => "foo%bar",
                "group" => "boofar"
            ]
        ];

        foreach ( $valid as $item ) {
            $this->assertTrue( MWUnit::areAttributesValid( $item ) );
        }

        foreach ( $invalid as $item ) {
            $this->assertFalse( MWUnit::areAttributesValid( $item ) );
        }

        $this->setMwGlobals( 'wgMWUnitForceCoversAnnotation', true );
        $this->assertFalse( MWUnit::areAttributesValid( [
            "name" => "foobar",
            "group" => "boofar"
        ] ) );
        $this->assertTrue( MWUnit::areAttributesValid( [
            "name" => "foobar",
            "group" => "boofar",
            "covers" => "boofar"
        ] ) );

        $this->setMwGlobals( 'wgMWUnitForceCoversAnnotation', false );
    }

    public function getTitleMock(int $namespace, string $content_model) {
        $title_mock = $this->createMock(\Title::class);
        $title_mock->method('getContentModel')
            ->willReturn($content_model);
        $title_mock->method('getNamespace')
            ->willReturn($namespace);

        return $title_mock;
    }

    public function getFatalStatusMock() {
        $status_mock = $this->getMockBuilder(\Status::class)
            ->getMock();
        $status_mock->expects($this->once())
            ->method('fatal')
            ->with($this->anything());

        return $status_mock;
    }

    public function getNonFatalStatusMock() {
        $status_mock = $this->getMockBuilder(\Status::class)
            ->getMock();
        $status_mock->expects($this->never())
            ->method('fatal')
            ->with($this->anything());

        return $status_mock;
    }
}