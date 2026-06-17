<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('orders:expire', function () {
    $count = \App\Models\Order::where('status', 'pending_payment')
        ->where('payment_status', 'pending')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->update(['status' => 'cancelled']);

    $this->info("Expired {$count} pending orders.");
})->purpose('Cancel pending payment orders after expiration time');
