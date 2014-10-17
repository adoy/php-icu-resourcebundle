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

class ResourceBundle implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $resourceDir;

    /**
     * @var bool
     */
    private $fallback;

    /**
     * @var array
     */
    private $root = array();

    /**
     * @cache array
     */
    private $cache;

    /**
     * @var Parser
     */
    private $parser = array();

    /**
     * @param string $locale
     * @param string $resourceDir
     */
    public function __construct($locale, $resourceDir, $fallback = true, Parser $parser)
    {
        $this->locale = $locale;
        if (!$this->resourceDir = realpath($resourceDir)) {
            throw new \InvalidArgumentException('Invalid resource directory: ' . $resourceDir);
        }
        $this->fallback = $fallback;
        $this->parser   = $parser;
        $this->cache    = &$this->root;
    }

    /**
     * @param string|int $key
     * @return mixed
     */
    public function get($key)
    {
        $locale = $this->locale;
        do {
            if (!$this->isLocaleLoaded($locale)) {
                $this->root += $this->loadLocale($locale);
            }
            if (isset($this->cache[$locale][$key])) {
                $val = $this->cache[$locale][$key];
                if (is_array($val)) {
                    $res = new ResourceBundle($locale, $this->resourceDir, false, $this->parser);
                    $res->root = &$this->root;
                    $res->cache = array($locale => &$val);
                    return $res;
                } elseif ($val instanceof ResourceAlias) {
                    return $this->resolveAlias($val);
                }
                return $val;
            }
        } while($this->fallback && $locale = $this->getParent($locale));
        return null;
    }

    /**
     * @param string|int $key
     * @return bool
     */
    public function exists($key) {
        $locale = $this->locale;
        do {
            if (!$this->isLocaleLoaded($locale)) {
                $this->root += $this->loadLocale($locale);
            }
            if (isset($this->cache[$locale][$key])) {
                return true;
            }
        } while($this->fallback && $locale = $this->getParent($locale));
        return false;

    }

    /**
     * @param ResourceAlias $alias
     * @return mixed
     */
    private function resolveAlias(ResourceAlias $alias)
    {
        $path = $alias->getIndexes();
        if (!$this->isLocaleLoaded($path[0])) {
            $this->root += $this->loadLocale($path[0]);
        }
        $node = $this->root;
        while ($key = array_shift($path)) {
            if (isset($node[$key])) {
                $node = $node[$key];
            } else {
                $node = null;
                break;
            }
        }
        return $node;
    }

    /**
     * @param string $locale
     */
    private function loadLocale($locale)
    {
        $fn = $this->resourceDir . '/' . $locale . '.txt';
        if (is_readable($fn)) {
            return $this->parser->parse($fn);
        }
        return array();
    }

    /**
     * @param string $locale
     * @return bool
     */
    private function isLocaleLoaded($locale)
    {
        return isset($this->root[$locale]);
    }

    /**
     * @param string $locale
     * @return string
     */
    private function getParent($locale)
    {
        if ('root' === $locale) {
            return null;
        } else if ($parent = substr($locale, 0, strrpos($locale, "_"))) {
            return $parent;
        } else {
            return 'root';
        }
    }

    /**
     * @param string $key
     * @return void
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * @throw \RuntimeException
     */
    public function offsetSet($key, $value)
    {
        throw new \RuntimeException('Read only resource');
    }

    /**
     * @param string|int $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    /**
     * @throw \RuntimeException
     */
    public function offsetUnset($key)
    {
        throw new \RuntimeException('Read only resource');
    }

    /**
     * @return Iterator
     */
    public function getIterator()
    {
        $locale = $this->locale;
        $array = array();
        do {
            if (!$this->isLocaleLoaded($locale)) {
                $this->root += $this->loadLocale($locale);
            }
            if (!empty($this->cache[$locale])) {
                $array = $this->cache[$locale];
                break;
            }
        } while($this->fallback && $locale = $this->getParent($locale));
        return new \ArrayIterator($array);
    }
}
