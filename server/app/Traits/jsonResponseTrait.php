<?php

namespace App\Traits;
use Illuminate\Http\JsonResponse;

trait JsonResponseTrait{

    public function successResponse(array|object $data = [], string $message = 'success', int $status = 200): JsonResponse{
        return response()->json([
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public function errorResponse(string $message = 'Error', array $errors = [] ,  int $status = 500): JsonResponse{
        return response()->json([
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}