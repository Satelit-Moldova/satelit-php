<?php
/**
 * Autoloader for hosting without Composer:
 *
 *     require __DIR__ . '/satelit-php/src/autoload.php';
 *     $satelit = new Satelit\Client('sat_live_...');
 *
 * With Composer, vendor/autoload.php does the same (PSR-4, namespace Satelit\).
 */
spl_autoload_register(function ($class) {
    if (strncmp($class, 'Satelit\\', 8) !== 0) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 8)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
