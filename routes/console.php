<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mail notifications are queued during requests. This worker drains only the mail queue
// and exits when it is empty, so the server scheduler can safely invoke it every minute.
Schedule::command('queue:work --queue=mail --stop-when-empty --tries=3 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping();
