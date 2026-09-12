<?php

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

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

$isSafeSlipPath = static function (?string $path): bool {
    return is_string($path)
        && $path !== ''
        && ! str_contains($path, "\0")
        && ! str_contains($path, '\\')
        && ! str_contains($path, '//')
        && ! str_starts_with($path, '/')
        && ! str_ends_with($path, '/')
        && preg_match('/^[A-Za-z]:/', $path) !== 1
        && preg_match('#(^|/)\.\.?(/|$)#', $path) !== 1;
};

$isAllowedPublicSlipPath = static fn (?string $path): bool => $isSafeSlipPath($path)
    && (str_starts_with($path, 'payment_slips/') || str_starts_with($path, 'slips/'));

$privateSlipPath = static fn (string $path): string => str_starts_with($path, 'slips/')
    ? 'payment_slips/'.substr($path, strlen('slips/'))
    : $path;

Artisan::command('payments:migrate-slips-private {--dry-run}', function () use ($isAllowedPublicSlipPath, $privateSlipPath) {
    $dryRun = (bool) $this->option('dry-run');
    $migrated = 0;
    $skipped = 0;
    $failed = 0;
    $source = Storage::disk('public');
    $destination = Storage::disk('local');

    Payment::query()
        ->where('slip_disk', 'public')
        ->whereNotNull('slip_path')
        ->select(['id', 'slip_path', 'slip_disk'])
        ->chunkById(100, function ($payments) use (
            $destination,
            $dryRun,
            $isAllowedPublicSlipPath,
            $privateSlipPath,
            $source,
            &$failed,
            &$migrated,
            &$skipped,
        ): void {
            foreach ($payments as $payment) {
                $path = $payment->slip_path;

                if (str_starts_with((string) $path, 'slips/demo-slip-')) {
                    $skipped++;

                    continue;
                }

                if (! $isAllowedPublicSlipPath($path)) {
                    $failed++;
                    $this->error("Payment {$payment->id}: unsafe slip path; skipped.");

                    continue;
                }

                if (! $source->exists($path)) {
                    $failed++;
                    $this->error("Payment {$payment->id}: source file is missing.");

                    continue;
                }

                $destinationPath = $privateSlipPath($path);

                if ($dryRun) {
                    $migrated++;
                    $this->line("Would migrate payment {$payment->id}: public:{$path} -> local:{$destinationPath}");

                    continue;
                }

                $destinationCreated = false;
                $databaseUpdated = false;

                try {
                    $sourceChecksum = $source->checksum($path);
                    if (! is_string($sourceChecksum) || $sourceChecksum === '') {
                        throw new RuntimeException('Could not checksum the public source file.');
                    }

                    if ($destination->exists($destinationPath)) {
                        $existingChecksum = $destination->checksum($destinationPath);
                        if (! is_string($existingChecksum) || ! hash_equals($sourceChecksum, $existingChecksum)) {
                            throw new RuntimeException('A different private destination file already exists.');
                        }
                    } else {
                        $stream = $source->readStream($path);
                        if (! is_resource($stream)) {
                            throw new RuntimeException('Could not read the public source file.');
                        }

                        try {
                            if (! $destination->put($destinationPath, $stream)) {
                                throw new RuntimeException('Could not write the private destination file.');
                            }
                            $destinationCreated = true;
                        } finally {
                            fclose($stream);
                        }
                    }

                    $destinationChecksum = $destination->checksum($destinationPath);
                    if (! $destination->exists($destinationPath)
                        || ! is_string($destinationChecksum)
                        || ! hash_equals($sourceChecksum, $destinationChecksum)) {
                        throw new RuntimeException('Private destination verification failed.');
                    }

                    $payment->forceFill([
                        'slip_disk' => 'local',
                        'slip_path' => $destinationPath,
                    ])->save();
                    $databaseUpdated = true;

                    if (! $source->delete($path) && $source->exists($path)) {
                        throw new RuntimeException('Could not delete the migrated public source file.');
                    }

                    $migrated++;
                    $this->info("Migrated payment {$payment->id}: public:{$path} -> local:{$destinationPath}");
                } catch (Throwable $exception) {
                    if (! $databaseUpdated && $destinationCreated) {
                        $destination->delete($destinationPath);
                    }

                    report($exception);
                    $failed++;
                    $this->error("Payment {$payment->id}: migration failed.");
                }
            }
        });

    $verb = $dryRun ? 'Would migrate' : 'Migrated';
    $this->info("{$verb} {$migrated} payment slips; skipped {$skipped}; {$failed} failed.");

    return $failed === 0 ? 0 : 1;
})->purpose('Move legacy public payment slips into private local storage');

Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();
