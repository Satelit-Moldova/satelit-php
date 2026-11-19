<?php

// The SDK through its own autoloader: the same file a host without Composer uses.
require __DIR__ . '/../src/autoload.php';

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

require __DIR__ . '/Fixtures.php';
