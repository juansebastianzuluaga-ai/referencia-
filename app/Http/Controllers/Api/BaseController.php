<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;
use Throwable;

class BaseController extends Controller
{
    public function sendResponse(mixed $data, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'success' => true,
            'message' => $message ?? 'Operacion realizada correctamente',
            'data' => $this->resolveData($data),
        ], $code);
    }

    public function sendError(string $message, mixed $errorData = [], int $code = 404, ?Throwable $exception = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'success' => false,
            'message' => $message,
            'data' => $this->resolveErrorData($errorData, $exception),
        ], $code);
    }

    public function sendException(Throwable $exception, ?string $message = null, int $code = 500): JsonResponse
    {
        return $this->sendError(
            $message ?? $exception->getMessage(),
            code: $code,
            exception: $exception,
        );
    }

    private function resolveData(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            $resourceData = $data->response(request())->getData(true);

            if (array_keys($resourceData) === ['data']) {
                return $resourceData['data'];
            }

            return $resourceData;
        }

        return $data;
    }

    private function resolveErrorData(mixed $errorData, ?Throwable $exception): mixed
    {
        $data = empty($errorData) ? [] : (array) $errorData;

        if ($exception instanceof Throwable && config('app.debug')) {
            $data['exception'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return empty($data) ? new stdClass : $data;
    }
}
