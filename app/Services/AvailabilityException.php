<?php

namespace App\Services;

use Exception;

class AvailabilityException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $code
    ) {
        parent::__construct($message);
    }
}
