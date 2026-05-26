<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MissingChild;
use Illuminate\Support\Facades\Storage;

$m = MissingChild::latest()->first();
if (!$m) {
    echo "No MissingChild found\n";
    exit(0);
}

echo "ID: {$m->id}\n";
echo "Case: {$m->case_code}\n";
echo "qr_code_path (DB): {$m->qr_code_path}\n";
if ($m->qr_code_path) {
    $exists = Storage::disk('public')->exists($m->qr_code_path) ? 'yes' : 'no';
    echo "File exists on disk: {$exists}\n";
    echo "Public URL: " . asset('storage/' . $m->qr_code_path) . "\n";
}

echo "Done.\n";
