<?php

namespace App\Http\Controllers;
use App\Models\UserPost;
use App\Service\CommunityService;
use Exception;
use Illuminate\Http\Request;


class CommunityController extends Controller{
    public function fetchPosts(Request $request){
        try{
            $userId = auth()->id();
            $page = (int) $request->query('page', 1);
            $paginatedPosts = CommunityService::getPosts($userId , $page);
            return $this->successResponse($paginatedPosts);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to fetch posts" , ["error" => $ex->getMessage()]);
        }
    }

    public function toggleLike($postId){
        try{
            $userId = auth()->id();

            $likeResp = CommunityService::toggleLike($userId , $postId);
            return $this->successResponse($likeResp);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to like post" , ["error" => $ex->getMessage()]);
        }
    }
}
