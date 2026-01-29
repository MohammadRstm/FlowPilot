<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvatarUploadRequest;
use App\Services\ProfileService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileController extends AuthenticatedController{

    public function getProfileDetails(Request $request){ 
        $viewerId = $this->authUser->id;// user viewing the profile (who made the request)
        $userId = (int) ($request->query('user_id') ?? $viewerId);// user being viewed

        $profileDetails = ProfileService::getProfileDetails(
            userId: $userId,
            viewerId: $viewerId
        );
        return $this->successResponse($profileDetails);
    }

    public function getFriends(string $name){
        $suggestions = UserService::getFriends($name , $this->authUser->id);
        return $this->successResponse($suggestions);
    }

    public function uploadAvatar(AvatarUploadRequest $request){
        $avatar = $request->file("avatar");

        ProfileService::uploadFile($this->authUser , $avatar);
        return $this->successResponse([] , "uploaded successfully");
    }
    
}
