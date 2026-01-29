<?php

namespace App\Services;

use App\Exceptions\UserFacingException;
use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Google_Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

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
        self::verifyUser($user , $credentials);

        $token = self::createToken($user);

        return self::authenticationReturnFormat($user , $token);
    }

    public static function googleLogin(array $data){
        $userData = self::getUserDataFromGoogle($data);
        $googleId = $userData["googleId"];
        $email = $userData["email"];

        $user = User::where('google_id', $googleId)->first();

        if(!$user)  {
            $user = User::where('email', $email)->first();// check if user exists with same email

            if($user){
                if($user->google_id && $user->google_id !== $googleId){
                    throw new UserFacingException("Google account already linked to another user");
                }

                $user->update(['google_id' => $googleId]);
            }else{
                $user = User::create(self::dto($userData));
            }
        }

        $token = self::createToken($user);

        return self::authenticationReturnFormat($user, $token);
    }

    public static function setPassword(Model $user , array $data){
        self::validatePassword($user , $data);

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
    private static function verifyUser(User | null $user , array $credentials){
        if(!$user){
            throw new UserFacingException("Invalid credentials");
        }

        if(!$user->password){
            throw new UserFacingException("This account uses Google login. Please continue with Google");
        }

        if(!Hash::check($credentials['password'], $user->password)){
            throw new UserFacingException("Invalid credentials");
        }
    }

    private static function getUserDataFromGoogle(array $data): array{
            $payload = self::verifyGoogleAccount($data);

            $googleId = $payload['sub'];
            return [
                "email" => $payload["email"],
                "firstName" => $payload["given_name"],
                "lastName" => $payload["family_name"],
                "googleId" => $googleId
            ];
    }

    private static function dto(array $data): array{
        return[
                "first_name" => $data["firstName"],
                "last_name" => $data["lastName"],
                "email" => $data["email"],
                "google_id" => $data["googleId"],
                "user_role_id" => env("USER_ROLE_ID"),
                "password" => null,
                "photo_url" => '',
                "email_verified_at" => now()
            ];
    }

    private static function validatePassword(User $user , array $data){
        if($user->password){
            if(!$data["current_password"] || !Hash::check($data["current_password"], $user->password)){
                throw new UserFacingException("Current password is incorrect");
            }
        }

        // // validate if password doesn't contain a capital letter
        // if(!preg_match('/[A-Z]/', $data["new_password"])){
        //     throw new UserFacingException("Password must contain at least one capital letter"); 
        // }

        // // validate if password length is at least 8 characters
        // if(strlen($data["new_password"]) < 8){
        //     throw new UserFacingException("Password must be at least 8 characters long");
        // }
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
        $expirationTime = (int)env('TOKEN_EXPIRATION_TIME', 604800); // 7 days default

        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => $now,
            'exp' => $now + $expirationTime,
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
}
