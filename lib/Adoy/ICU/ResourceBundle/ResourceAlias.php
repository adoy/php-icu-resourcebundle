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

class ResourceAlias
{
    /**
     * @var string
     */
    private $ref;

    /**
     * @param string $ref 
     */
    public function __construct($ref)
    {
        $this->ref = $ref;
    }

    /**
     * @return array
     */
    public function getIndexes()
    {
        return explode('/', $this->ref);
    }
}
