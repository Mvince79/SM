<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $alias = 'missing-child.clue-form';
    try {
        $exists = app('livewire')->exists($alias);
        echo "Alias: $alias => exists: " . ($exists ? 'true' : 'false') . "\n";
    } catch (\Throwable $e) {
        echo "Livewire exists error: " . $e->getMessage() . "\n";
    }
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
