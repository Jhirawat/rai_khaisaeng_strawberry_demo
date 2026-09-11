<?php

use App\Models\Order;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:expire', function () {
    $expired = 0;
    $failed = 0;
    $workflow = app(OrderWorkflowService::class);

    Order::query()
        ->select('id')
        ->where('status', 'pending_payment')
        ->where('payment_status', 'pending')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->whereHas('payment', fn ($query) => $query
            ->where('status', 'pending')
            ->whereIn('method', $workflow->expirablePaymentMethods()))
        ->chunkById(100, function ($orders) use ($workflow, &$expired, &$failed): void {
            foreach ($orders as $order) {
                try {
                    if ($workflow->expire($order)) {
                        $expired++;
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $failed++;
                    $this->error("Failed to expire order {$order->id}.");
                }
            }
        });

    $this->info("Expired {$expired} pending orders; {$failed} failed.");

    return $failed === 0 ? 0 : 1;
})->purpose('Cancel pending payment orders after expiration time');

Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();
