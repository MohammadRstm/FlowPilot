<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoogleLoginRequest;
use App\Http\Requests\LinkN8nAccountRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SetPasswordRequest;
use App\Service\AuthService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller{

    public function register(RegisterRequest $request){
        try{
            $data = $request->validated();
            $response = AuthService::createUser($data);

            return $this->successResponse($response);
        }catch(Exception $ex){
           return $this->errorResponse("User signup failed" , ["error" => $ex->getMessage()]);
        }
    }   

    public function login(LoginRequest $request){
        try{
            $credentials = $request->validated();
            $response = AuthService::login($credentials);

            return $this->successResponse($response);
        }catch(Exception $ex){
           return $this->errorResponse("User login failed" , ["error" => $ex->getMessage()]);
        }
    }

    public function me(Request $request){
        $user = $request->user();

        
        return $this->successResponse([
            'id'         => $user->id,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
        ]);
    }

    public function googleLogin(GoogleLoginRequest $request){
        try{
            $data = $request->validated();
            $response = AuthService::googleLogin($data);
            return $this->successResponse($response);
        }catch(Exception $ex){
            return $this->errorResponse("Google login failed" , ["error" => $ex->getMessage()]);
        }
    }

    public function setPassword(SetPasswordRequest $request){
        try{
            $user = $request->user();
            $data = $request->validated();

            AuthService::setPassword($user , $data);
            return $this->successResponse([], 'Password set successfully');
            
        }catch(Exception $ex){
            return $this->errorResponse("Set password failed" , ["error" => $ex->getMessage()]);
        }
    }

    public function unlinkGoogleAccount(Request $request){
        try{
            $user = $request->user();
            AuthService::unlinkGoogle($user);
            return $this->successResponse([], 'Google account unlinked successfullly');
            
        }catch(Exception $ex){
            return $this->errorResponse("Unlinking google account failed" , ["error" => $ex->getMessage()]);
        }
    }

    public function linkN8nAccount(LinkN8nAccountRequest $request){
        try{
            $user = $request->user();
            $data = $request->validated();
            AuthService::linkN8nAccount($user , $data);
            return $this->successResponse([], 'N8n account linked successfullly');
            
        }catch(Exception $ex){
            return $this->errorResponse("Failed to link n8n account" , ["error" => $ex->getMessage()]);
        }
    }
}