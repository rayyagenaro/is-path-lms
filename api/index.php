<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Vercel serverless entry point
|--------------------------------------------------------------------------
|
| A Vercel Function has a read-only project filesystem. Laravel still needs
| writable directories for compiled Blade views and transient framework data,
| so those directories live in the function's /tmp volume.
|
*/

$runtimeDirectories = [
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
];

foreach ($runtimeDirectories as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

require __DIR__.'/../public/index.php';
