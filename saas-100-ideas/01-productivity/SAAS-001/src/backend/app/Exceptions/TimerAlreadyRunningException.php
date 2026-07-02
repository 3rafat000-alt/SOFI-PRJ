<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TimerAlreadyRunningException extends Exception
{
    public function __construct(string $message = 'Timer already running on another task.', int $code = 409)
    {
        parent::__construct($message, $code);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'CONFLICT',
                'message' => $this->getMessage(),
            ],
        ], 409);
    }
}
