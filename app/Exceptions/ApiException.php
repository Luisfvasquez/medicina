<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Base exception for RFC 7807 Problem Details responses.
 *
 * All API exceptions must extend this class to ensure consistent
 * error format across the application.
 */
abstract class ApiException extends \Exception
{
    public function __construct(
        protected string $type,
        protected string $title,
        protected int $status,
        protected ?string $detail = null,
        protected ?string $instance = null,
        protected ?array $invalidParams = null,
    ) {
        parent::__construct($title);
    }

    /**
     * Render the exception as a RFC 7807 Problem Details JSON response.
     */
    public function render(): JsonResponse
    {
        $payload = [
            'type'    => $this->type,
            'title'   => $this->title,
            'status'  => $this->status,
            'detail'  => $this->detail ?? $this->getMessage(),
            'instance' => $this->instance ?? '/' . request()->path(),
        ];

        if ($this->invalidParams !== null) {
            $payload['invalidParams'] = $this->invalidParams;
        }

        return response()->json($payload, $this->status);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
