<?php

namespace Satelit\Cache;

/**
 * A cache of files in one directory, for hosting without APCu, Redis or
 * Memcached. Each entry is a small JSON file; writes go to a temp
 * file and are renamed, so a reader never sees half an entry.
 *
 *     $cache = new FileCache(__DIR__ . '/var/satelit-cache');
 *
 * Keep the directory outside the web root (or deny it in .htaccess).
 */
final class FileCache implements CacheInterface
{
    /** @var string */
    private $dir;

    /**
     * @param string $dir created (0700) when missing
     */
    public function __construct($dir)
    {
        $this->dir = rtrim((string) $dir, '/\\');
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0700, true) && !is_dir($this->dir)) {
            throw new \RuntimeException('Cannot create the cache directory ' . $this->dir);
        }
    }

    public function get($key, $default = null)
    {
        $file = $this->file($key);
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return $default;
        }
        $entry = json_decode($raw, true);
        if (!is_array($entry) || !array_key_exists('v', $entry) || !isset($entry['exp'])) {
            return $default;
        }
        if ($entry['exp'] !== 0 && $entry['exp'] <= time()) {
            @unlink($file);
            return $default;
        }
        return $entry['v'];
    }

    public function set($key, $value, $ttl = null)
    {
        $exp = $ttl === null ? 0 : time() + (int) $ttl;
        if ($ttl !== null && (int) $ttl <= 0) {
            return $this->delete($key);
        }
        $json = json_encode(array('exp' => $exp, 'v' => $value));
        if ($json === false) {
            return false;
        }
        $tmp = @tempnam($this->dir, '.tmp');
        if ($tmp === false || @file_put_contents($tmp, $json, LOCK_EX) === false) {
            if ($tmp !== false) {
                @unlink($tmp);
            }
            return false;
        }
        @chmod($tmp, 0600);
        if (!@rename($tmp, $this->file($key))) {
            @unlink($tmp);
            return false;
        }
        return true;
    }

    public function delete($key)
    {
        $file = $this->file($key);
        return !is_file($file) || @unlink($file);
    }

    public function clear()
    {
        $ok = true;
        foreach ((array) glob($this->dir . '/satelit-*.json') as $file) {
            $ok = @unlink($file) && $ok;
        }
        return $ok;
    }

    private function file($key)
    {
        return $this->dir . '/satelit-' . substr(hash('sha256', (string) $key), 0, 32) . '.json';
    }
}
