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

class Lexer {

    /**
     * List of Tokens
     */
    const T_COLON    = 256,
          T_RRANGEEX = 257,
          T_LRANGEEX = 258,
          T_COMMA    = 259,
          T_INT      = 260,
          T_QUOTED   = 261,
          T_TERM     = 262;

    /**
     * List of patterns
     * @var array
     */
    private $tokenPatterns = array(
        '[ \t\n\r]+'          => false,
        '//.*'                => false,
        ':'                   => self::T_COLON,
        '{'                   => self::T_LRANGEEX,
        '}'                   => self::T_RRANGEEX,
        ','                   => self::T_COMMA,
        '[0-9]+'              => self::T_INT,
        '"(?:[^"\\\]|\\\.)*"' => self::T_QUOTED,
        '[^ \t\n\r:,{}]+'     => self::T_TERM,
    );

    /**
     * Token map
     * @var array
     */
    private $tokenMap;

    /**
     * Regex to match the tokens
     * @var string
     */
    protected $regex;

    /**
     * Input data
     * @var string
     */
    protected $data;

    /**
     * Char position
     * @var int
     */
    private $count;

    /**
     * Filename
     * @var string
     */
    private $fileName;

    /**
     * Line
     * @var int
     */
    private $line;

    /**
     * Current token
     * @var array
     */
    public $token;

    /**
     * Look ahead token
     * @var array
     */
    public $lookAhead;

    /**
     * Lexer constructor
     *
     * @param string|null $fileName File to tokenize
     * @return void
     */
    public function __construct($fileName = null) {
        $this->regex    = '#\G(' . implode(')|\G(', array_keys($this->tokenPatterns)) . ')#A';
        $this->tokenMap = array_values($this->tokenPatterns);
        if (null !== $fileName) {
            $this->setInput($fileName);
        }
    }

    /**
     * Change the input file of the Lexer and reset its state
     *
     * @param string $fileName File to tokenize
     * @return void
     */
    public function setInput($fileName)
    {
        if (!is_readable($fileName)) {
            throw new LexingException('Unable to read file: ' . $fileName, LexingException::FILE_NOT_FOUND, $fileName);
        }
        $this->fileName  = $fileName;
        $this->data      = file_get_contents($fileName);
        $this->reset();
    }

    /**
     * Reset the state of the Lexer
     *
     * @return void
     */
    public function reset()
    {
        $this->count     = 0;
        $this->line      = 1;
        $this->token     = null;
        $this->lookAhead = null;
        $this->yylex();
    }

    /**
     * Move forward in the token list
     *
     * @throw LexingException
     * @return boolean true if a token was found, false otherwise
     */
    public function yylex() {
        $this->token = $this->lookAhead;
        $this->lookAhead = $this->getNextToken();
        return (bool) $this->token;
    }

    /**
     * Checks if a given token type matches the current lookahead
     *
     * @param mixed $token
     * @return bool
     */
    public function isNextToken($token) {
        return null !== $this->lookAhead && $this->lookAhead['type'] === $token;
    }

    /**
     * Get the next token from the input
     *
     * @throw LexingException
     * @return array
     */
    private function getNextToken()
    {
        while (isset($this->data[$this->count])) {
            if (!preg_match($this->regex, $this->data, $matches, null, $this->count)) {
                // @codeCoverageIgnoreStart
                $msg = sprintf('Unexpected character "%s"', $this->data[$this->count]);
                throw new LexingException($msg, LexingException::NO_TOKEN_FOUND, $this->fileName, $this->line);
                // @codeCoverageIgnoreEnd
            }
            for ($i = 1; '' === $matches[$i]; ++$i);
            $this->count += strlen($matches[0]);
            $this->line  += substr_count($matches[0], "\n");

            if ($this->tokenMap[$i - 1]) {
                return array(
                    'type'     => $this->tokenMap[$i - 1],
                    'value'    => $matches[$i],
                    'line'     => $this->line,
                );
            }
        }
        return false;
    }

    /**
     * Gets the literal for a given token
     *
     * @param int $token
     * @return string
     */
    public function getLiteral($token)
    {
        $className = get_class($this);
        $refClass = new \ReflectionClass($className);
        $constants = $refClass->getConstants();

        foreach ($constants as $name => $value) {
            if ($value === $token) {
                return $name;
            }
        }

        return $token;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }
}
