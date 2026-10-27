<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Respon sukses dengan amplop message + data.
     */
    protected function success(string $message, mixed $data = null, int $status = 200, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'message' => $message,
            'data' => $data,
        ], $extra), $status);
    }

    /**
     * Respon sukses untuk koleksi terpaginasi: message + data + meta + links.
     */
    protected function paginated(string $message, LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $resourceClass::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
                'last' => $paginator->url($paginator->lastPage()),
            ],
        ], 200);
    }

    /**
     * Respon gagal (404, 409, 422 bisnis, 500) dengan amplop yang sama.
     */
    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = [
            'message' => $message,
            'data' => null,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    protected function notFound(string $message): JsonResponse
    {
        return $this->error($message, 404);
    }
}
