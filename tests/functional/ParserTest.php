<?php

namespace Adoy\ICU\ResourceBundle;

use org\bovigo\vfs\vfsStream;
use Adoy\ICU\ResourceBundle\ParsingException;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    private function fileFromStr($str) {
        vfsStream::setup('root', null, array('file.xml' => $str, 'file2.txt' => 'Hello world'));
        return vfsStream::url('root') .'/file.xml';
    }

    public function setUp() {
        $this->parser = new Parser(new Lexer());
    }

    private function setExpectedParseError($msg = null, $code = ParsingException::FATAL_ERROR)
    {
        $this->setExpectedException('Adoy\ICU\ResourceBundle\ParsingException', $msg, $code);
    }

    /**
     * @dataProvider validResourceBundleFileDataProvider
     */
    public function testParseValidResourceBundleFileWillReturnAnArrayRepresentingTheResource($source, $expectedResult)
    {
        $fileName = $this->fileFromStr($source);
        $result = $this->parser->parse($fileName);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider invalidResourceBundleFileDataProvider
     */
    public function testParseInvalidResourceBundleWillThrowAParsingException($source)
    {
        $this->setExpectedParseError();
        $fileName = $this->fileFromStr($source);
        $this->parser->parse($fileName);
    }

    public static function validResourceBundleFileDataProvider()
    {
        return array(
            array(
                'root { foo:int { 10 }}',
                array('root' => array('foo' => 10))
            ),
            array(
                'root { foo:string { "bar" }}',
                array('root' => array('foo' => 'bar'))
            ),
            array(
                'root { foo:string { "bar" "baz" }}',
                array('root' => array('foo' => 'barbaz'))
            ),
            array(
                'root { foo:intvector { 10, 20, 30 }}',
                array('root' => array('foo' => array(10, 20, 30)))
            ),
            array(
                'root { foo:array { "foo", "bar", "baz" }}',
                array('root' => array('foo' => array("foo", "bar", "baz")))
            ),
            array(
                'root { foo { "foo", "bar", "baz" } }',
                array('root' => array('foo' => array("foo", "bar", "baz")))
            ),
            array(
                'root { foo:array { {"foo"} { 10 } }}',
                array('root' => array('foo' => array("foo", 10)))
            ),
            array(
                'root { foo:table { foo {"foo"} bar {"bar"} }}',
                array('root' => array('foo' => array("foo" => "foo", "bar" => "bar")))
            ),
            array(
                'root { foo:bin { abcd } }',
                array('root' => array('foo' => base64_decode('abcd')))
            ),
            array(
                'root { foo:alias { "root/bar" } }',
                array('root' => array('foo' => new ResourceAlias('root/bar')))
            ),
            array(
                'root { foo:import { "file2.txt" } }',
                array('root' => array('foo' => 'Hello world'))
            ),
            array(
                'root { foo { { "STR" }}}',
                array('root' => array('foo' => array("STR")))
            ),
            array(
                'root { foo { bar:string { "baz" }}}',
                array('root' => array('foo' => array('bar' => 'baz')))
            ),
        );
    }

    public static function invalidResourceBundleFileDataProvider()
    {
        return array(
            array('root:int { 10 }'),
            array('root { foo:int { "str" }}'),
            array('root { foo:string { 10 }}'),
            array('root { foo:intvector { 10 "foo" }}'),
            array('root { foo:array { { : } }}'),
            array('root { foo:array { {"foo"} { 10, {} } }}'),
            array('root { foo:bin { 10 } }'),
            array('root { foo:alias { 10 } }'),
            array('root { foo:import { "unknown.txt" } }'),
            array('root { foo:import { 10 } }'),
            array('root { foo:table { :string {"foo"} } }'),
            array('root { foo:unknown { "foo" } }'),
            array('root { foo: { "foo" } }'),
            array('root:table '),
        );
    }
}

