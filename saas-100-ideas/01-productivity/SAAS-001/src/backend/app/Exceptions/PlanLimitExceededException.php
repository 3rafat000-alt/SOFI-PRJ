<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PlanLimitExceededException extends Exception
{
    public function __construct(string $message = 'Plan limit exceeded.', int $code = 429)
    {
        parent::__construct($message, $code);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'PLAN_LIMIT_EXCEEDED',
                'message' => $this->getMessage(),
            ],
        ], 429);
    }
}
