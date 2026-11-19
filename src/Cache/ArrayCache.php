<?php

namespace Satelit\Cache;

/**
 * An in-memory cache for one PHP process (a CLI script, a worker, tests).
 */
final class ArrayCache implements CacheInterface
{
    /** @var array<string,array{0:int,1:mixed}> */
    private $items = array();

    public function get($key, $default = null)
    {
        if (!isset($this->items[$key])) {
            return $default;
        }
        list($exp, $value) = $this->items[$key];
        if ($exp !== 0 && $exp <= time()) {
            unset($this->items[$key]);
            return $default;
        }
        return $value;
    }

    public function set($key, $value, $ttl = null)
    {
        if ($ttl !== null && (int) $ttl <= 0) {
            return $this->delete($key);
        }
        $this->items[$key] = array($ttl === null ? 0 : time() + (int) $ttl, $value);
        return true;
    }

    public function delete($key)
    {
        unset($this->items[$key]);
        return true;
    }

    public function clear()
    {
        $this->items = array();
        return true;
    }
}
