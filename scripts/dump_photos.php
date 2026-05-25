<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FoundChild;
use App\Models\Photo;

$found = FoundChild::with('photos')->latest()->first();
if (! $found) {
    echo "NO_FOUND_CHILD\n";
    exit(0);
}

$data = [
    'found_id' => $found->id,
    'photos' => $found->photos()->get(['id','path_original','path_thumb','is_primary'])->map(function($p){
        $pArr = $p->toArray();
        $path = $pArr['path_thumb'] ?: $pArr['path_original'];
        $pArr['public_url'] = \Illuminate\Support\Facades\URL::to(\Illuminate\Support\Facades\Storage::url($path));
        return $pArr;
    })->toArray(),
];

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
