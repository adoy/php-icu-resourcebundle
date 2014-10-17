<?php

namespace Adoy\ICU\ResourceBundle;

use org\bovigo\vfs\vfsStream;

class LexerTest extends \PHPUnit_Framework_TestCase
{

    const INVALID_TOKEN = 300;

    private function fileFromStr($str) {
        vfsStream::setup('root', null, array('file.xml' => $str));
        return vfsStream::url('root') .'/file.xml';
    }

    public function testConstructorWithoutFileNameWillNotOpenFile()
    {
        $lexer = new Lexer();
        $this->assertNull($lexer->getFileName());
    }

    public function testConstructorWithFileNameWillOpenTheSpecifiedFileName()
    {
        $fileName = $this->fileFromStr('');
        $lexer = new Lexer($fileName);
        $this->assertSame($fileName, $lexer->getFileName());
    }

    public function testSetInputWillOpenTheSpecifiedFile()
    {
        $fileName = $this->fileFromStr('');
        $lexer = new Lexer();
        $lexer->setInput($fileName);
        $this->assertSame($fileName, $lexer->getFileName());
    }

    /**
     * @expectedException Adoy\ICU\ResourceBundle\LexingException
     * @expectedExceptionCode Adoy\ICU\ResourceBundle\LexingException::FILE_NOT_FOUND
     */
    public function testSetInputWithInvalidFileNameWillThrowLexingException()
    {
        new Lexer('Foo');
    }

    /**
     * @dataProvider LexemDataProvider
     */
    public function testYYLexWillCorrectlyFindLexem($data, $lexem)
    {
        $fileName = $this->fileFromStr($data);
        $lexer = new Lexer($fileName);
        $this->assertTrue($lexer->yylex());
        $expectedToken = array(
            'type'  => $lexem,
            'value' => $data,
            'line'  => 1,
        );
        $this->assertSame($expectedToken, $lexer->token);
    }

    /**
     * @dataProvider LexemDataProvider
     */
    public function testIsNextTokenWillReturnTrueIfTheNextTokenIsTheOneSpecifiedAndFalseOtherwise($data, $lexem)
    {
        $fileName = $this->fileFromStr($data);
        $lexer = new Lexer($fileName);
        $this->assertTrue($lexer->isNextToken($lexem));
        $this->assertFalse($lexer->isNextToken($lexem+1));
    }

    public function testYYLexWillReturnFalseAtEndOfFile()
    {
        $fileName = $this->fileFromStr('');
        $lexer = new Lexer($fileName);
        $this->assertFalse($lexer->yylex());
    }

    /**
     * @dataProvider LexemDataProvider
     */
    public function testYYLexWillIgnoreSpacesAndComments($data, $lexem)
    {
        $fileName = $this->fileFromStr('// Comment' . PHP_EOL . ' ' . $data);
        $lexer = new Lexer($fileName);
        $this->assertTrue($lexer->yylex());
        $expectedToken = array(
            'type'  => $lexem,
            'value' => $data,
            'line'  => 2,
        );
        $this->assertSame($expectedToken, $lexer->token);

    }

    public function testGetLiteralWillReturnCorrectLiteralValue()
    {
        $lexer = new Lexer();
        $this->assertSame('T_TERM', $lexer->getLiteral(Lexer::T_TERM));
        $this->assertSame('T_COLON', $lexer->getLiteral(Lexer::T_COLON));
        $this->assertSame('T_RRANGEEX', $lexer->getLiteral(Lexer::T_RRANGEEX));
        $this->assertSame('T_LRANGEEX', $lexer->getLiteral(Lexer::T_LRANGEEX));
        $this->assertSame('T_COMMA', $lexer->getLiteral(Lexer::T_COMMA));
        $this->assertSame('T_INT', $lexer->getLiteral(Lexer::T_INT));
        $this->assertSame('T_QUOTED', $lexer->getLiteral(Lexer::T_QUOTED));
        $this->assertSame(self::INVALID_TOKEN, $lexer->getLiteral(self::INVALID_TOKEN));
    }

    public static function lexemDataProvider()
    {
        return array(
            array('Foo', Lexer::T_TERM),
            array(':', Lexer::T_COLON),
            array('{', Lexer::T_LRANGEEX),
            array('}', Lexer::T_RRANGEEX),
            array(',', Lexer::T_COMMA),
            array('200', Lexer::T_INT),
            array('"foo bar baz"', Lexer::T_QUOTED),
        );
    }
}

