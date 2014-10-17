<?php

namespace Adoy\ICU\ResourceBundle;

class ParsingExceptionTest extends \PHPUnit_Framework_TestCase
{
    const A_FILENAME = 'FOO',
          A_LINE_NB  = 20,
          A_MESSAGE  = 'A Message',
          A_CODE     = 30;

    public function testConstructor()
    {
        $e = new ParsingException(self::A_MESSAGE, self::A_CODE, self::A_FILENAME, self::A_LINE_NB);
        $this->assertSame(self::A_MESSAGE, $e->getMessage());
        $this->assertSame(self::A_CODE, $e->getCode());
        $this->assertSame(self::A_FILENAME, $e->getFile());
        $this->assertSame(self::A_LINE_NB, $e->getLine());
    }
}

