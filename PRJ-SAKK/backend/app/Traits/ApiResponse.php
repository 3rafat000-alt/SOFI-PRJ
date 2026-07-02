<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success response.
     */
    protected function success(mixed $data = null, string $message = 'تم بنجاح', int $code = 200, array $extra = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }

        return response()->json($response, $code);
    }

    /**
     * Error response.
     */
    protected function error(string $message = 'حدث خطأ', int $code = 400, mixed $data = null, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Success with meta (paginated).
     */
    protected function successWithMeta(mixed $data, mixed $meta, string $message = 'تم بنجاح', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $code);
    }

    /**
     * Created response.
     */
    protected function created(mixed $data = null, string $message = 'تم الإنشاء بنجاح'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * No content response.
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Validation error response.
     */
    protected function validationError(string $message = 'بيانات غير صالحة', array $errors = []): JsonResponse
    {
        return $this->error($message, 422, null, $errors);
    }

    /**
     * Not found response.
     */
    protected function notFound(string $message = 'غير موجود'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Unauthorized response.
     */
    protected function unauthorized(string $message = 'غير مصرح'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Forbidden response.
     */
    protected function forbidden(string $message = 'ممنوع'): JsonResponse
    {
        return $this->error($message, 403);
    }
}
