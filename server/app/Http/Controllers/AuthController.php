<?php

namespace App\Http\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Google\Client as GoogleClient;

class AuthController extends Controller{
    public function register(Request $request){
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'user_role_id'      => env('USER_ROLE_ID'), // default role
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'],
            'email'             => $data['email'],
            'password'          => $data['password'], // hashed cast on model
            'photo_url'         => '',
            'email_verified_at' => now(),
        ]);

        $token = $this->createToken($user);

        return $this->successResponse([
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
            ],
        ], 'registered', 201);
    }

    public function login(Request $request){
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return $this->errorResponse('Invalid credentials', [], 401);
        }

        if (!$user->password) {
            return $this->errorResponse(
                'This account uses Google login. Please continue with Google.',
                [],
                401
            );
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Invalid credentials', [], 401);
        }
        $token = $this->createToken($user);

        return $this->successResponse([
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
            ],
        ]);
    }

    public function me(Request $request){
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('Unauthenticated', [], 401);
        }

        return $this->successResponse([
            'id'         => $user->id,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
        ]);
    }

    public function googleLogin(Request $request){
        $data = $request->validate([
            'idToken' => ['required', 'string'],
        ]);

        $client = new GoogleClient([
            'client_id' => env('GOOGLE_CLIENT_ID'),
        ]);


        $payload = $client->verifyIdToken($data['idToken']);

        if(!$payload){
            return $this->errorResponse('Invalid Google token', [], 401);
        }

        if (!($payload['email_verified'] ?? false)) {
            return $this->errorResponse('Google email not verified', [], 401);
        }


        $email = $payload['email'];
        $googleId = $payload['sub'];
        $firstName = $payload['given_name'] ?? '';
        $lastName = $payload['family_name'] ?? '';

        $user = User::where('google_id', $googleId)->first();

        if(
            User::where('google_id', $googleId)
                ->where('id', '!=', optional($user)->id)
                ->exists()
        ) {
            return $this->errorResponse('Google account already linked', [], 409);
        }

        if(!$user){
            $user = User::where('email', $email)->first();
        }

        if(!$user){
            $user = User::create([
                'user_role_id'      => env('USER_ROLE_ID'),
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'email'             => $email,
                'google_id'         => $googleId,
                'password'          => null, // Google-only account
                'photo_url'         => $payload['picture'] ?? '',
                'email_verified_at' => now(),
            ]);
        }

        if (!$user->google_id) {
            $user->update(['google_id' => $googleId]);
        }

        $token = $this->createToken($user);

        return $this->successResponse([
            'token' => $token,
            'user' => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
            ],
        ]);
    }

    public function setPassword(Request $request){
        $user = $request->user();

        if ($user->password) {
            return $this->errorResponse('Password already set', [], 400);
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return $this->successResponse([], 'Password set successfully');
    }



    private function getJwtSecret(): string{
        $secret = env('JWT_SECRET');

        if (! $secret) {
            throw new \RuntimeException('JWT_SECRET environment variable is not set');
        }

        return $secret;
    }

    private function createToken(User $user): string{
        $now = time();

        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => $now,
            'exp' => $now + (60 * 60 * 24 * 7), // 7 days
        ];

        return JWT::encode($payload, $this->getJwtSecret(), 'HS256');
    }
}