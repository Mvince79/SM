<?php

use function Livewire\Volt\state;
use Illuminate\Support\Facades\Http;

state([
    'stats' => [],
]);

$mount = function () {
    $this->stats = Http::get(
        url('/api/v1/stats')
    )->json();
};

?>

<div>
    ...
</div>