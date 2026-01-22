<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Service\ProfileService;
use Illuminate\Http\Request;

class FollowerController extends Controller{
    
    public function followUser(Request $request , int $toBeFollowed){ 
        $userId = $request->user()->id;

        ProfileService::toggeleFollow($userId, $toBeFollowed);
        return $this->successResponse([] , "User followed successfully");
    }

    public function isFollowed(Request $request , int $toBeChecked){ 
        $userId = $request->user()->id;

        $response = ProfileService::isFollowingUser($toBeChecked , $userId);
        return $this->successResponse($response);
    }
}
