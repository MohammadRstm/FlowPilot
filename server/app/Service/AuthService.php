<?php

namespace App\Service;

use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Google_Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService{

    public static function createUser(array $userData, int $isFromGoogle = 0){
        $user = User::create([
            'user_role_id'      => env('USER_ROLE_ID'), // default role
            'first_name'        => $userData['first_name'],
            'last_name'         => $userData['last_name'],
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
            throw new Exception("Invalid credentials");
        }


        if (!$user->password) {
            throw new Exception("This account uses Google login. Please continue with Google");
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            throw new Exception("Invalid credentials");
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
            throw new Exception("Google account already linked");
        }


        if(!$user){
            $user = self::getUserByEmail($userData);
        }


        if(!$user){
            $isFromGoogle = 1;
            self::createUser($userData ,$isFromGoogle);
        }


        if (!$user->google_id) {
            $user->update(['google_id' => $googleId]);
        }

        $token = self::createToken($user);


        return self::authenticationReturnFormat($user, $token);
    }

    public static function setPassword(Model $user , array $data){
        if($user->password){
            if(!$data["current"] || !Hash::check($data["current"], $user->password)){
                throw new Exception("Current password is incorrect");
            }
        }

        $user->password = Hash::make($data["new"]);
        $user->save();
    }

    public static function unlinkGoogle(User $user): void{
        if (!$user->password) {
            throw new Exception("Set a password before unlinking Google");
        }

        $user->google_id = null;
        $user->save();
    }


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
            'exp' => $now + (60 * 60 * 24 * 7), // 7 days
        ];

        return JWT::encode($payload, self::getJwtSecret(), 'HS256');
    }

    private static function getJwtSecret(): string{
        $secret = env('JWT_SECRET');

        if (! $secret) {
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
