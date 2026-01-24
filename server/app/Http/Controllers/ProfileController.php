<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvatarUploadRequest;
use App\Service\ProfileService;
use App\Service\UserService;
use Illuminate\Http\Request;

class ProfileController extends Controller{

    public function getProfileDetails(Request $request){ 
        $viewerId = $request->user()->id;// user viewing the profile (who made the request)
        $userId = (int) ($request->query('user_id') ?? $viewerId);// user being viewed

        $profileDetails = ProfileService::getProfileDetails(
            userId: $userId,
            viewerId: $viewerId
        );
        return $this->successResponse($profileDetails);
    }

    public function getFriends(Request $request , string $name){
        $userId = $request->user()->id;

        $suggestions = UserService::getFriends($name , $userId);
        return $this->successResponse($suggestions);
    }

    public function uploadAvatar(AvatarUploadRequest $request){
        $user = $request->user();
        $avatar = $request->file("avatar");

        ProfileService::uploadFile($user , $avatar);
        return $this->successResponse([] , "uploaded successfully");
    }
    
}
