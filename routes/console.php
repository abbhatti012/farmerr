<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Jobs\FetchShopifyOrders;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\CreateZohoInvoices;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('fetch:shopify-orders', function () {
    // Dispatch the job synchronously
    FetchShopifyOrders::dispatchSync();
    $this->info('Shopify orders fetched successfully.');
})->purpose('Fetch Shopify orders');

Artisan::command('schedule:run-fetch-shopify-orders', function () {
    $schedule = app(Schedule::class);
    $schedule->command('fetch:shopify-orders')->everyThirtyMinutes();
    $schedule->call(function() {
        Log::info('Scheduled fetch shopify orders command executed.');
    })->everyThirtyMinutes();
})->describe('Run the schedule for fetching Shopify orders.');


Artisan::command('create:zoho-invoices', function () {
    \App\Jobs\CreateZohoInvoices::dispatchSync();
    $this->info('ZohoInvoices orders processed successfully.');
})->purpose('Manually dispatch the CreateZohoInvoices job');


