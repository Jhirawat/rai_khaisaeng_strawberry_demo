<?php

namespace App\Support;

use Illuminate\Database\QueryException;

final class ForeignKeyConstraintViolation
{
    public static function causedBy(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo ?? [];
        $sqlState = (string) ($errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($errorInfo[1] ?? 0);
        $message = strtolower((string) ($exception->getPrevious()?->getMessage() ?? $exception->getMessage()));

        if ($sqlState === '23503') {
            return true;
        }

        if ($sqlState !== '23000') {
            return false;
        }

        if (in_array($driverCode, [547, 1451, 1452], true)) {
            return true;
        }

        return in_array($driverCode, [19, 787], true)
            && str_contains($message, 'foreign key');
    }
}
