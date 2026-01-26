<?php

namespace App\Service;

use App\Exceptions\UserFacingException;
use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Google_Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthService{

    public static function createUser(array $userData, int $isFromGoogle = 0){
        $user = User::create([
            'user_role_id'      => env('USER_ROLE_ID'), // default role
            'first_name'        => $userData['firstName'],
            'last_name'         => $userData['lastName'],
            'email'             => $userData['email'],
            'password'          => $isFromGoogle ? null : $userData['password'],
            'photo_url'         => '',
            'email_verified_at' => now(),
        ]);

        $token = self::createToken($user);
        
        return self::authenticationReturnFormat($user , $token);
    }

    public static function login(array $credentials){
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            throw new UserFacingException("Invalid credentials");
        }

        if (!$user->password) {
            throw new UserFacingException("This account uses Google login. Please continue with Google");
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            throw new UserFacingException("Invalid credentials");
        }

        $token = self::createToken($user);

        return self::authenticationReturnFormat($user , $token);
    }

    public static function googleLogin(array $data){
        $payload = self::verifyGoogleAccount($data);

        $googleId = $payload['sub'];
        $userData = [
            "email" => $payload["email"],
            "firstName" => $payload["given_name"],
            "lastName" => $payload["family_name"]
        ];

        $user = User::where('google_id', $googleId)->first();
        if($user && self::googleAccountAlreadyLinkedWithDifferentUser($user , $googleId)){
            throw new UserFacingException("Google account already linked");
        }

        if(!$user){
            $user = User::create([
                "first_name" => $userData["firstName"],
                "last_name" => $userData["lastName"],
                "email" => $userData["email"],
                "user_role_id" => env("USER_ROLE_ID"),
                "password" => null,
                "photo_url" => '',
                "email_verified_at" => now()
            ]);
        }

        if(!$user){
            $isFromGoogle = 1;
            $user = self::createUser($userData ,$isFromGoogle);
        }

        if (!$user->google_id) {
            $user->update(['google_id' => $googleId]);
        }

        $token = self::createToken($user);

        return self::authenticationReturnFormat($user, $token);
    }

    public static function setPassword(Model $user , array $data){
        if($user->password){
            if(!$data["current_password"] || !Hash::check($data["current_password"], $user->password)){
                throw new UserFacingException("Current password is incorrect");
            }
        }

        $user->password = Hash::make($data["new_password"]);
        $user->save();
    }

    public static function unlinkGoogle(User $user): void{
        if (!$user->password) {
            throw new UserFacingException("Set a password before unlinking Google");
        }

        $user->google_id = null;
        $user->save();
    }

    public static function linkN8nAccount(Model $user , array $data){
        $user->n8n_base_url = $data["base_url"];
        $user->n8n_api_key = $data["api_key"];
        $user->save();
    }

    /** helpers */
    private static function verifyGoogleAccount(array $data){
        $client = new Google_Client([
            'client_id' => env('GOOGLE_CLIENT_ID'),
        ]);


        $payload = $client->verifyIdToken($data['idToken']);

        if(!$payload){
            throw new Exception("Invalid Google token");
        }

        if(!($payload['email_verified'] ?? false)){
            throw new Exception("Google email not varified");
        }

        return $payload;
    }

    private static function authenticationReturnFormat(array | Model $user , string $token){
        return[
            "token" => $token,
            'user'  => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
            ],
        ];
    }

    private static function createToken(User $user): string{
        $now = time();

        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => $now,
            'exp' => $now + env('TOKEN_EXPIRATION_TIME'), // 7 days
        ];

        return JWT::encode($payload, self::getJwtSecret(), 'HS256');
    }

    private static function getJwtSecret(): string{
        $secret = env('JWT_SECRET');

        if(!$secret){
            throw new \RuntimeException('JWT_SECRET environment variable is not set');
        }

        return $secret;
    }

    private static function googleAccountAlreadyLinkedWithDifferentUser(Model $user , string | int $googleId){
        return User::where('google_id', $googleId)
                ->where('id', '!=', optional($user)->id)
                ->exists();
    }

    private static function getUserByEmail(array $userData){
        return User::where('email', $userData["email"])->first();
    }
}
