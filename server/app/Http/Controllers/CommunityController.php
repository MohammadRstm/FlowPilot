<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentPostRequest;
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

    public function toggleLike(int $postId){
        try{
            $userId = auth()->id();

            $likeResp = CommunityService::toggleLike($userId , $postId);
            return $this->successResponse($likeResp);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to like post" , ["error" => $ex->getMessage()]);
        }
    }

    public function export(int $postId){
        try{
            $userId = auth()->id();

            $exportResp = CommunityService::export($userId , $postId);
            return $this->successResponse($exportResp);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to export post" , ["error" => $ex->getMessage()]);
        }
    }

    public function toggleCommentLike(int $commentId){
        try{
            $userId = auth()->id();

            $likedResp = CommunityService::toggleCommentLike($userId , $commentId);
            return $this->successResponse($likedResp);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to like comment" , ["error" => $ex->getMessage()]);
        }
    }

    public function getPostComments(int $postId){
        try{
            $comments = CommunityService::getComments($postId);
            return $this->successResponse($comments);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to get post comments" , ["error" => $ex->getMessage()]);
        }
    }

    public function postComment($postId , CommentPostRequest $request){
        try{
            $content = $request->validated()["content"];
            $userId = auth()->id();
            $submitResp = CommunityService::postComment($userId , $content , $postId);
            return $this->successResponse($submitResp);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to post comment" , ["error" => $ex->getMessage()]);
        }
    }
}
