<?php
/*
 * This file is part of php-icu-resourcebundle
 *
 * (c) Pierrick Charron <pierrick@adoy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adoy\ICU\ResourceBundle;

class Parser
{
    /**
     * @var Lexer
     */
    private $lexer;

    /**
     * @var array
     */
    private $formatStack = array();

    /**
     * @var string
     */
    private $cwd;

    /**
     * Constructor
     *
     * @param Lexer $lexer
     * @return void
     */
    public function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * Parser an ICU resource text file
     *
     * @param string $fileName ICU resource text file to parse
     * @return array
     */
    public function parse($fileName)
    {
        $this->lexer->setInput($fileName);
        $this->cwd = dirname($this->lexer->getFileName());
        return $this->startStatement();
    }

    /**
     * start ::= T_TERM optional_format T_LRANGEEX table_value T_RRANGEEX.
     */
    private function startStatement()
    {
        $lang = $this->nameStatement();
        if (($format = $this->formatStatement()) && ($format !== 'table')) {
            throw new ParsingException('Root node must have a table format', ParsingException::FATAL_ERROR, $this->lexer->getFileName(), $this->lexer->token['line']);
        }
        array_push($this->formatStack, 'table');
        $this->match(Lexer::T_LRANGEEX);
        $value = array($lang => $this->tableValueStatement());
        $this->match(Lexer::T_RRANGEEX);
        array_pop($this->formatStack);
        $this->match(NULL, 'EOF');
        return $value;
    }

    /**
     * value ::= table_value.
     * value ::= string_value.
     * value ::= int_value.
     * value ::= intvector_value.
     * value ::= array_value.
     * value ::= bin_value.
     * value ::= import_value.
     * value ::= alias_value.
     */
    private function valueStatement()
    {
        switch (end($this->formatStack)) {
            case 'table':
                return $this->tableValueStatement();
            case 'string':
                return $this->stringValueStatement();
            case 'int':
                return $this->intValueStatement();
            case 'intvector':
                return $this->intVectorValueStatement();
            case 'array':
                return $this->arrayValueStatement();
            case 'bin':
                return $this->binValueStatement();
            case 'import':
                return $this->importValueStatement();
            case 'alias':
                return $this->aliasValueStatement();
            default:
                if ($this->lexer->isNextToken(Lexer::T_QUOTED) || $this->lexer->isNextToken(Lexer::T_INT)) {
                    $val = $this->scalarValueStatement();
                    if ($this->lexer->isNextToken(Lexer::T_COMMA)) {
                        $val = array($val);
                        do {
                            $this->match(Lexer::T_COMMA);
                            $val[] = $this->scalarValueStatement();
                        } while ($this->lexer->isNextToken(Lexer::T_COMMA));
                    }
                    return $val;
                } elseif ($this->lexer->isNextToken(Lexer::T_TERM)) {
                    return $this->tableValueStatement();
                } elseif ($this->lexer->isNextToken(Lexer::T_LRANGEEX)) {
                    return $this->arrayValueStatement();
                }
                $this->parseError('T_QUOTED, T_INT, T_TERM or T_RRANGEEX');
        } // @codeCoverageIgnore
    } // @codeCoverageIgnore

    /**
     * bin_value ::= T_TERM.
     */
    private function binValueStatement()
    {
        if ($this->lexer->isNextToken(Lexer::T_TERM)) {
            $this->lexer->yylex();
            return base64_decode($this->lexer->token['value']);
        }
        $this->parseError('T_TERM');
    } // @codeCoverageIgnore

    /**
     * import_value ::= T_QUOTED.
     */
    private function importValueStatement()
    {
        if ($this->lexer->isNextToken(Lexer::T_QUOTED)) {
            $this->lexer->yylex();
            $fileName = $this->cwd . DIRECTORY_SEPARATOR . substr($this->lexer->token['value'], 1, strlen($this->lexer->token['value']) -2);
            if (is_readable($fileName)) {
                return file_get_contents($fileName);
            }
            throw new ParsingException('Enable to read file: ' . $fileName, ParsingException::FATAL_ERROR, $this->lexer->getFileName(), $this->lexer->token['line']);
        }
        $this->parseError('T_QUOTED');
    } // @codeCoverageIgnore

    /**
     * array_value ::= optional_format T_LRANGEEX value T_RRANGEEX.
     * array_value ::= scalar_list
     *
     * scalar_list ::= scalar_list scalar.
     * scalar_list ::= scalar.
     */
    private function arrayValueStatement()
    {
        $array = array();
        if ($this->lexer->isNextToken(Lexer::T_COLON) || $this->lexer->isNextToken(Lexer::T_LRANGEEX)) {
            do {
                array_push($this->formatStack, $this->formatStatement());
                $this->match(Lexer::T_LRANGEEX);
                $array[] = $this->valueStatement();
                $this->match(Lexer::T_RRANGEEX);
                array_pop($this->formatStack);
            } while (!$this->lexer->isNextToken(Lexer::T_RRANGEEX));
        } else {
            $array[] = $this->scalarValueStatement();
            while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
                $this->match(Lexer::T_COMMA);
                $array[] = $this->scalarValueStatement();
            }
        }
        return $array;
    }

    /**
     * scalar ::= T_QUOTED.
     * scalar ::= T_INT.
     */
    private function scalarValueStatement()
    {
        if ($this->lexer->isNextToken(Lexer::T_QUOTED)) {
            return $this->readSeparatedStringValue();
        } elseif ($this->lexer->isNextToken(Lexer::T_INT)) {
            return $this->intValueStatement();
        }
        $this->parseError('T_QUOTED or T_INT');
    } // @codeCoverageIgnore

    /**
     * intvector_value ::= intvector_value T_COMMA T_INT.
     * intvector_value ::= T_INT.
     */
    private function intVectorValueStatement()
    {
        $intVector[] = $this->intValueStatement();
        while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $intVector[] = $this->intValueStatement();
        }
        if (!$this->lexer->isNextToken(Lexer::T_RRANGEEX)) {
            $this->parseError('T_COMMA or T_RRANGEEX');
        } // @codeCoverageIgnore
        return $intVector;
    }

    /**
     * string_value ::= string_value T_QUOTED.
     * string_value ::= T_QUOTED.
     */
    private function stringValueStatement()
    {
        if ($this->lexer->isNextToken(Lexer::T_QUOTED)) {
            return $this->readSeparatedStringValue();
        }
        $this->parseError('T_QUOTED');
    } // @codeCoverageIgnore

    /**
     * alias_value ::= T_QUOTED.
     */
    private function aliasValueStatement()
    {
        if ($this->lexer->isNextToken(Lexer::T_QUOTED)) {
            $this->lexer->yylex();
            return new ResourceAlias(substr($this->lexer->token['value'], 1, strlen($this->lexer->token['value']) -2));
        }
        $this->parseError('T_QUOTED');
    } // @codeCoverageIgnore

    private function readSeparatedStringValue()
    {
        $str = '';
        while ($this->lexer->isNextToken(Lexer::T_QUOTED)) {
            $this->lexer->yylex();
            $str .= substr($this->lexer->token['value'], 1, strlen($this->lexer->token['value']) -2);
        }
        return $str;
    }

    /**
     * int_value ::= T_INT.
     */
    private function intValueStatement()
    {
        if ($this->lexer->isNextToken(Lexer::T_INT)) {
            $this->lexer->yylex();
            return (int) $this->lexer->token['value'];
        }
        $this->parseError('T_INT');
    } // @codeCoverageIgnore

    /**
     * table_value ::= optional_name optional_format T_LRANGEEX value T_RRANGEEX.
     *
     * optional_name ::= T_TERM.
     * optional_name ::= .
     *
     */
    private function tableValueStatement()
    {
        $table = array();
        do {
            $name = $this->nameStatement(true);
            array_push($this->formatStack, $this->formatStatement());
            $this->match(Lexer::T_LRANGEEX);
            $table[$name] = $this->valueStatement();
            $this->match(Lexer::T_RRANGEEX);
            array_pop($this->formatStack);
        } while (!$this->lexer->isNextToken(Lexer::T_RRANGEEX));

        return $table;
    }

    /**
     * name := TERM.
     * name := INT.
     */
    private function nameStatement($mandatory = false)
    {
        if ($this->lexer->isNextToken(Lexer::T_TERM) || $this->lexer->isNextToken(Lexer::T_INT)) {
            $this->lexer->yylex();
            return $this->lexer->token['value'];
        } elseif ($mandatory) {
            $this->parseError('T_TERM or T_INT');
        } // @codeCoverageIgnore
    } // @codeCoverageIgnore

    /**
     * optional_format ::= format.
     * optional_format ::= .
     *
     * format := T_COLON T_TERM
     */
    private function formatStatement()
    {
        if ($this->lexer->isNextToken(Lexer::T_COLON)) {
            $this->match(Lexer::T_COLON);
            if ($this->lexer->isNextToken(Lexer::T_TERM)) {
                $format = strtolower($this->lexer->lookAhead['value']);
                if (!in_array($format, array('table', 'array', 'string', 'bin', 'import', 'int', 'intvector', 'alias'))) {
                    $this->parseError('table, array, string, bin, import, int, intvector or alias');
                } // @codeCoverageIgnore
                $this->lexer->yylex();
                return $format;
            }
            $this->parseError('table, array, string, bin, import, int, intvector or alias');
        } // @codeCoverageIgnore
    }

    /**
     * @param mixed $token
     * @param mixed $str
     * @return void
     */
    private function match($token, $str = null)
    {
        if ($this->lexer->lookAhead['type'] !== $token) {
            $this->parseError($str ? $str : $this->lexer->getLiteral($token));
        } // @codeCoverageIgnore
        $this->lexer->yylex();
    }

    /**
     * @param string $expected
     * @return void
     */
    private function parseError($expected = '')
    {
        $token = $this->lexer->lookAhead;
        if ($token) {
            $currentToken = $this->lexer->getLiteral($token['type']) . ' (' . $token['value'] . ')';
        } else {
            $currentToken = 'EOF';
        }
        $msg = 'Parse error: ';
        $msg .= ($expected) ? 'expected ' . $expected . ' got ' . $currentToken : 'unexpected ' . $currentToken;
        throw new ParsingException($msg, ParsingException::FATAL_ERROR, $this->lexer->getFileName(), $token['line']);
    } // @codeCoverageIgnore
}
