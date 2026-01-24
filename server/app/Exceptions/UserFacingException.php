<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserFacingException extends Exception{

    protected int $status;

    public function __construct(string $message, int $status = 400){
        parent::__construct($message);
        $this->status = $status;
    }

    public function getStatus(): int{
        return $this->status;
    }

    public function render(Request $request): JsonResponse{
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 404);
    }
    
}
