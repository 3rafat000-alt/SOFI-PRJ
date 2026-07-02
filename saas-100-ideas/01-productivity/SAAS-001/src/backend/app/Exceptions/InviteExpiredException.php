<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InviteExpiredException extends Exception
{
    public function __construct(string $message = 'Invitation has expired.', int $code = 410)
    {
        parent::__construct($message, $code);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'INVITE_EXPIRED',
                'message' => $this->getMessage(),
            ],
        ], 410);
    }
}
