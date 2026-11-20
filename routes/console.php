<?php

use App\Support\PilotItemAnalysis;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('iq:pilot-report {--revision=4}', function (): void {
    $revision = filter_var($this->option('revision'), FILTER_VALIDATE_INT);
    if ($revision === false || $revision < 1) {
        $this->error('A positive bank revision is required.');

        return;
    }

    $report = PilotItemAnalysis::forRevision($revision);
    $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
})->purpose('Read-only aggregate item and distractor diagnostics; never produces IQ norms.');
