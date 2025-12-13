<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\OrderPlaced::class => [
            \App\Listeners\SendOrderConfirmationMessage::class,
        ],
    ];
    protected $observers = [
    \App\Models\Order::class => \App\Observers\OrderObserver::class,
];

}
