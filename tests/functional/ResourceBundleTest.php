<?php

namespace Adoy\ICU\ResourceBundle;

class ResourceBundleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidResourceDir()
    {
        new ResourceBundle('root', '@', true, new Parser(new Lexer));
    }

    public function testGetWithoutInheritance()
    {
        $rb = new ResourceBundle('root', __DIR__, true, new Parser(new Lexer));
        $str = $rb->get('menu')->get('id');
        $this->assertSame('mainmenu', $str);
    }

    public function testGetWithInheritance()
    {
        $rb = new ResourceBundle('fr_CA', __DIR__, true, new Parser(new Lexer));
        $str = $rb->get('menu')->get('id');
        $this->assertSame('mainmenu', $str);
    }

    public function testGetWithInvalidValue()
    {
        $rb = new ResourceBundle('fr_CA', __DIR__, true, new Parser(new Lexer));
        $this->assertNull($rb['INVALID']);
    }

    public function testAlias()
    {
        $rb = new ResourceBundle('root', __DIR__, true, new Parser(new Lexer));
        $str = $rb['foo']['bar']['baz'];
        $this->assertSame('fr_CA:foobar', $str);
    }

    public function testAliasInvalid()
    {
        $rb = new ResourceBundle('root', __DIR__, true, new Parser(new Lexer));
        $str = $rb['foo']['bar']['invalid'];
        $this->assertNull($str);
    }

    public function testTraversable()
    {
        $rb = new ResourceBundle('fr_DE', __DIR__, true, new Parser(new Lexer));
        $array = iterator_to_array($rb);
        $expected = array (
            'foo' => 'Foo',
            'bar' => 'Bar',
            'baz' => 'Baz',
        );
        $this->assertSame($expected, $array);
    }

    public function testExists()
    {
        $rb = new ResourceBundle('root', __DIR__, true, new Parser(new Lexer));
        $this->assertTrue(isset($rb['menu']));
        $this->assertFalse(isset($rb['invalid']));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testOffsetSet()
    {
        $rb = new ResourceBundle('root', __DIR__, true, new Parser(new Lexer));
        $rb['foo'] = 'bar';
    }

    /**
     * @expectedException RuntimeException
     */
    public function testOffUnset()
    {
        $rb = new ResourceBundle('root', __DIR__, true, new Parser(new Lexer));
        unset($rb['foo']);
    }
}

