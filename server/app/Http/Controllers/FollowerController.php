<?php

namespace App\Http\Controllers;

use App\Service\ProfileService;
use Illuminate\Http\Request;

class FollowerController extends AuthenticatedController{
    
    public function followUser(int $toBeFollowed){ 
        $userId = $this->authUser->id;

        $result = ProfileService::toggeleFollow($userId, $toBeFollowed);
        return $this->successResponse($result , "User followed successfully");
    }

    public function isFollowed(int $toBeChecked){ 
        $userId = $this->authUser->id;

        $response = ProfileService::isFollowingUser($toBeChecked , $userId);
        return $this->successResponse($response);
    }
}
