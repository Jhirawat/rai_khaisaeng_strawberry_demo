<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidOrderTransition extends RuntimeException
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    ) {
        parent::__construct(sprintf(
            'ไม่สามารถเปลี่ยนสถานะคำสั่งซื้อจาก "%s" เป็น "%s" ได้',
            $from,
            $to,
        ));
    }
}
