<?php

// src/Helpers/ApiResponse.php
class ApiResponse
{
    public static function success(mixed $data, int $status = 200): Response
    {
        return json([
            'status'  => 'success',
            'data'    => $data,
            'meta'    => ['version' => 'v1', 'timestamp' => now()],
        ], $status);
    }

    public static function error(string $message, int $status, array $errors = []): Response
    {
        return json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    public static function paginated(Collection $items, Paginator $p): Response
    {
        return json([
            'status' => 'success',
            'data'   => $items,
            'meta'   => [
                'page'       => $p->currentPage(),
                'per_page'   => $p->perPage(),
                'total'      => $p->total(),
                'last_page'  => $p->lastPage(),
            ],
        ]);
    }
}