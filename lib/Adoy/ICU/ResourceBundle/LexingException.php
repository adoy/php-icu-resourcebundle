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

class LexingException extends \Exception {

    const FILE_NOT_FOUND = 1;
    const NO_TOKEN_FOUND = 2;

    /**
     * @param string $message
     * @param int    $line
     * @return void
     */
    public function __construct($message, $code, $file, $line = 0)
    {
        parent::__construct($message, $code);
        if ($file) {
            $this->file = $file;
        }
        if ($line) {
            $this->line = $line;
        }
    }
}
