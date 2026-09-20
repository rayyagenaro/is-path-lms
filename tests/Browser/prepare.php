<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$file = storage_path('framework/testing/browser-audit.sqlite');
if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
if (!file_exists($file)) touch($file);
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $file]);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\DatabaseSeeder::class, '--force' => true]);
echo "Browser fixture ready in isolated SQLite database.\n";
