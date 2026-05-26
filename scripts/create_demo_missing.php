<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
// Boot the framework so facades, Eloquent, etc. work
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MissingChild;
use Illuminate\Support\Facades\Storage;

echo "Creating demo MissingChild...\n";

$data = [
    'user_id' => 1,
    'full_name' => 'Demo Child T13',
    'gender' => 'UNKNOWN',
    'province_id' => 1,
    'contact_name' => 'Parent Demo',
    'contact_phone' => '0123456789',
    'missing_date' => date('Y-m-d'),
];

$missing = MissingChild::create($data);
$missing->refresh();

echo "Created ID: {$missing->id}\n";
echo "Case code: {$missing->case_code}\n";
echo "DB qr_code_path: {$missing->qr_code_path}\n";

if ($missing->qr_code_path) {
    $exists = Storage::disk('public')->exists($missing->qr_code_path) ? 'yes' : 'no';
    echo "QR file exists on disk: {$exists}\n";
    if ($exists === 'yes') {
        echo "Public URL: " . asset('storage/' . $missing->qr_code_path) . "\n";
    }
}

echo "Done.\n";
