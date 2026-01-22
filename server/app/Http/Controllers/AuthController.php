<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoogleLoginRequest;
use App\Http\Requests\LinkN8nAccountRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SetPasswordRequest;
use App\Service\AuthService;
use App\Service\UserService;
use Illuminate\Http\Request;

class AuthController extends Controller{

    public function register(RegisterRequest $request){
        $data = $request->validated();
        $response = AuthService::createUser($data);
        return $this->successResponse($response);
    }   

    public function login(LoginRequest $request){
        $credentials = $request->validated();
        $response = AuthService::login($credentials);

        return $this->successResponse($response);
    }

    public function me(Request $request){
        $user = $request->user();    
        return $this->successResponse([
            'id'         => $user->id,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
            'photo_url'  => $user->photo_url
         ]);
    }

    public function googleLogin(GoogleLoginRequest $request){
        $data = $request->validated();
        $response = AuthService::googleLogin($data);
        return $this->successResponse($response);
    }

    public function setPassword(SetPasswordRequest $request){
        $user = $request->user();
        $data = $request->validated();

        AuthService::setPassword($user , $data);
        return $this->successResponse([], 'Password set successfully');
    }

    public function unlinkGoogleAccount(Request $request){
        $user = $request->user();
        AuthService::unlinkGoogle($user);
        return $this->successResponse([], 'Google account unlinked successfullly');
    }

    public function linkN8nAccount(LinkN8nAccountRequest $request){
        $user = $request->user();
        $data = $request->validated();
        AuthService::linkN8nAccount($user , $data);
        return $this->successResponse([], 'N8n account linked successfullly');
    }

    public function getUserAccount(Request $request){
        $userId = $request->user()->id;

        $userAccountInfo = UserService::getUserAccount($userId);
        return $this->successResponse($userAccountInfo);
    }
}