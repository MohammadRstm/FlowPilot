<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response{
        $header = $request->header('Authorization');

        Log::debug("here");

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Log::debug("here");
        

        $token = substr($header, 7);

        try {
            $secret = env('JWT_SECRET');

            if(!$secret){
                throw new \RuntimeException('JWT_SECRET environment variable is not set');
            }
        Log::debug("here");


            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $userId  = $decoded->sub ?? null;

            if(!$userId){
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        Log::debug("here");

            $user = User::find($userId);

            if(!$user){
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        Log::debug("here");
            
            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);
        } catch (\Throwable $e) {
            Log::debug($e->getMessage());
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}