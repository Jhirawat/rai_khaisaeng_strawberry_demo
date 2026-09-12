<?php

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

$isAllowedPrivateSlipPath = static fn (?string $path): bool => $isSafeSlipPath($path)
    && str_starts_with($path, 'payment_slips/');

$privateSlipPath = static fn (string $path): string => str_starts_with($path, 'slips/')
    ? 'payment_slips/'.substr($path, strlen('slips/'))
    : $path;

Artisan::command('payments:migrate-slips-private {--dry-run}', function () use (
    $isAllowedPrivateSlipPath,
    $isAllowedPublicSlipPath,
    $privateSlipPath,
) {
    $dryRun = (bool) $this->option('dry-run');
    $migrated = 0;
    $skipped = 0;
    $failed = 0;
    $source = Storage::disk('public');
    $destination = Storage::disk('local');

    Payment::query()
        ->whereIn('slip_disk', ['public', 'local'])
        ->whereNotNull('slip_path')
        ->select(['id', 'slip_path', 'slip_disk'])
        ->chunkById(100, function ($payments) use (
            $destination,
            $dryRun,
            $isAllowedPrivateSlipPath,
            $isAllowedPublicSlipPath,
            $privateSlipPath,
            $source,
            &$failed,
            &$migrated,
            &$skipped,
        ): void {
            foreach ($payments as $payment) {
                $path = $payment->slip_path;

                if ($payment->slip_disk === 'local') {
                    if (! $isAllowedPrivateSlipPath($path)) {
                        continue;
                    }

                    $publicPaths = array_values(array_unique([
                        $path,
                        'slips/'.substr($path, strlen('payment_slips/')),
                    ]));
                    $existingPublicPaths = array_values(array_filter(
                        $publicPaths,
                        fn (string $publicPath): bool => $source->exists($publicPath),
                    ));

                    if ($existingPublicPaths === []) {
                        continue;
                    }

                    try {
                        if (! $destination->exists($path)) {
                            throw new RuntimeException('The committed private destination file is missing.');
                        }

                        $destinationChecksum = $destination->checksum($path);
                        if (! is_string($destinationChecksum) || $destinationChecksum === '') {
                            throw new RuntimeException('Could not checksum the committed private destination file.');
                        }

                        foreach ($existingPublicPaths as $publicPath) {
                            $publicChecksum = $source->checksum($publicPath);
                            if (! is_string($publicChecksum)
                                || ! hash_equals($destinationChecksum, $publicChecksum)) {
                                throw new RuntimeException('A lingering public file differs from the private destination.');
                            }
                        }

                        $publicDescription = implode(', ', array_map(
                            fn (string $publicPath): string => "public:{$publicPath}",
                            $existingPublicPaths,
                        ));

                        if ($dryRun) {
                            $migrated++;
                            $this->line("Would migrate payment {$payment->id}: {$publicDescription} -> local:{$path}");

                            continue;
                        }

                        foreach ($existingPublicPaths as $publicPath) {
                            if (! $source->delete($publicPath) && $source->exists($publicPath)) {
                                throw new RuntimeException('Could not delete a lingering public source file.');
                            }
                        }

                        $migrated++;
                        $this->info("Migrated payment {$payment->id}: {$publicDescription} -> local:{$path}");
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                        $this->error("Payment {$payment->id}: migration failed.");
                    }

                    continue;
                }

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

                $destinationExistedBeforeCopy = $destination->exists($destinationPath);
                $metadataWriteAccepted = false;

                try {
                    $sourceChecksum = $source->checksum($path);
                    if (! is_string($sourceChecksum) || $sourceChecksum === '') {
                        throw new RuntimeException('Could not checksum the public source file.');
                    }

                    if ($destinationExistedBeforeCopy) {
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

                    DB::transaction(function () use (
                        $destinationPath,
                        &$metadataWriteAccepted,
                        $payment,
                    ): void {
                        $saved = $payment->forceFill([
                            'slip_disk' => 'local',
                            'slip_path' => $destinationPath,
                        ])->save();

                        if (! $saved) {
                            throw new RuntimeException('Could not update the payment slip metadata.');
                        }

                        $metadataWriteAccepted = true;
                    });

                    $persisted = Payment::query()
                        ->select(['slip_disk', 'slip_path'])
                        ->find($payment->getKey());

                    if ($persisted?->slip_disk !== 'local' || $persisted->slip_path !== $destinationPath) {
                        throw new RuntimeException('Payment slip metadata verification failed.');
                    }

                    if (! $source->delete($path) && $source->exists($path)) {
                        throw new RuntimeException('Could not delete the migrated public source file.');
                    }

                    $migrated++;
                    $this->info("Migrated payment {$payment->id}: public:{$path} -> local:{$destinationPath}");
                } catch (Throwable $exception) {
                    if (! $metadataWriteAccepted
                        && ! $destinationExistedBeforeCopy
                        && $destination->exists($destinationPath)) {
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
