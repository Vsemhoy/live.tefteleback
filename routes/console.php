<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\Demo\DemoCleanupService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:cleanup {--ttl= : Minutes demo overlay records must live before cleanup}', function (DemoCleanupService $cleanup) {
    $ttl = $this->option('ttl');
    $summary = $cleanup->cleanup($ttl !== null ? (int) $ttl : null);

    $this->info('Demo cleanup complete.');
    $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
})->purpose('Delete expired demo-user overlay records without touching seeded baseline data');
