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

class ParsingException extends \Exception
{
    const FATAL_ERROR = 1;
    /**
     * @param mixed $message
     * @param int $code
     * @param mixed $fileName
     * @param int $line
     * @return void
     */
    public function __construct($message = '', $code = self::FATAL_ERROR, $fileName = null, $line = 0)
    {
        parent::__construct($message, $code);
        if (null !== $fileName) {
            $this->file = $fileName;
        }
        if (0 !== $line) {
            $this->line = $line;
        }
    }
}
