<?php

namespace Satelit\Cache;

/**
 * The part of PSR-16 (Psr\SimpleCache\CacheInterface) this SDK uses. Any
 * PSR-16 cache object works as well: the client only calls get() and set().
 */
interface CacheInterface
{
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed the value, or $default on a miss or an expired entry
     */
    public function get($key, $default = null);

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl seconds
     * @return bool
     */
    public function set($key, $value, $ttl = null);

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key);

    /**
     * @return bool
     */
    public function clear();
}
