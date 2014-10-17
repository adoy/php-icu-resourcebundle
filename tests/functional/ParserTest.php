<?php

namespace Adoy\ICU\ResourceBundle;

use org\bovigo\vfs\vfsStream;

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

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testInvalidFormatForRootNode()
    {
        $fileName = $this->fileFromStr('root:int { 10 }');
        $this->parser->parse($fileName);
    }

    public function testParseInt()
    {
        $fileName = $this->fileFromStr('root { foo:int { 10 }}');
        $res = $this->parser->parse($fileName);
        $this->assertSame(array('root' => array('foo' => 10)), $res);
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testParseErrorInt()
    {
        $fileName = $this->fileFromStr('root { foo:int { "str" }}');
        $this->parser->parse($fileName);
    }

    public function testParseString()
    {
        $fileName = $this->fileFromStr('root { foo:string { "bar" }}');
        $res = $this->parser->parse($fileName);
        $this->assertSame(array('root' => array('foo' => 'bar')), $res);
    }

    public function testParseConcatenatedString()
    {
        $fileName = $this->fileFromStr('root { foo:string { "bar" "baz" }}');
        $res = $this->parser->parse($fileName);
        $this->assertSame(array('root' => array('foo' => 'barbaz')), $res);
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testParseErrorString()
    {
        $fileName = $this->fileFromStr('root { foo:string { 10 }}');
        $this->parser->parse($fileName);
    }

    public function testParseIntVector()
    {
        $fileName = $this->fileFromStr('root { foo:intvector { 10, 20, 30 }}');
        $res = $this->parser->parse($fileName);
        $this->assertSame(array('root' => array('foo' => array(10, 20, 30))), $res);
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testParseErrorIntVector()
    {
        $fileName = $this->fileFromStr('root { foo:intvector { 10 "foo" }}');
        $this->parser->parse($fileName);
    }

    public function testParseArray()
    {
        $fileName = $this->fileFromStr('root { foo:array { "foo", "bar" }}');
        $res = $this->parser->parse($fileName);
        $this->assertSame(array('root' => array('foo' => array("foo", "bar"))), $res);
    }

    public function testParseArray2()
    {
        $fileName = $this->fileFromStr('root { foo:array { {"foo"} {"bar"} }}');
        $res = $this->parser->parse($fileName);
        $this->assertSame(array('root' => array('foo' => array("foo", "bar"))), $res);
    }

    public function testParseTable()
    {
        $fileName = $this->fileFromStr('root { foo:table { foo {"foo"} bar {"bar"} }}');
        $res = $this->parser->parse($fileName);
        $this->assertSame(array('root' => array('foo' => array("foo" => "foo", "bar" => "bar"))), $res);
    }

    public function testParseBin()
    {
        $fileName = $this->fileFromStr('root { foo:bin { abcd } }');
        $res = $this->parser->parse($fileName);
        $this->assertSame(array('root' => array('foo' => base64_decode('abcd'))), $res);
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testParseErrorBin()
    {
        $fileName = $this->fileFromStr('root { foo:bin { 10 } }');
        $this->parser->parse($fileName);
    }

    public function testImport()
    {
        $fileName = $this->fileFromStr('root { foo:import { "file2.txt" } }');
        $res = $this->parser->parse($fileName);
        $this->assertSame(array('root' => array('foo' => 'Hello world')), $res);
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testImportInvalidFileName()
    {
        $fileName = $this->fileFromStr('root { foo:import { "unknown.txt" } }');
        $res = $this->parser->parse($fileName);
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testParseError()
    {
        $fileName = $this->fileFromStr('root { foo:import { 10 } }');
        $res = $this->parser->parse($fileName);
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testInvalidNameResourceBundleName()
    {
        $fileName = $this->fileFromStr('root { foo:table { :string {"foo"} } }');
        $this->parser->parse($fileName);
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testInvalidFormat()
    {
        $fileName = $this->fileFromStr('root { foo:unknown { "foo" } }');
        $this->parser->parse($fileName);
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testParseErrorFormat()
    {
        $fileName = $this->fileFromStr('root { foo: { "foo" } }');
        $this->parser->parse($fileName);
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\ParsingException
     */
    public function testMatch()
    {
        $fileName = $this->fileFromStr('root:table ');
        $this->parser->parse($fileName);
    }
}

