<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentPostRequest;
use App\Http\Requests\CreatePostRequest;
use App\Service\CommunityService;
use Exception;



class CommunityController extends Controller{

   

    public function toggleLike(Request $request , int $postId){
        $userId = $request->user()->id;

        $likeResp = CommunityService::toggleLike($userId , $postId);
        return $this->successResponse($likeResp);
    }

    public function export(Request $request , int $postId){
        $userId = $request->user()->id;

        $exportResp = CommunityService::export($userId , $postId);
        return $this->successResponse($exportResp);
    }

    public function toggleCommentLike(Request $request , int $commentId){
        $userId = $request->user()->id;

        $likedResp = CommunityService::toggleCommentLike($userId , $commentId);
        return $this->successResponse($likedResp);
    }

    public function getPostComments(int $postId){
        $comments = CommunityService::getComments($postId);
        return $this->successResponse($comments);
    }

    public function postComment($postId , CommentPostRequest $request){
        $content = $request->validated()["content"];
        $userId = $request->user()->id;

        $submitResp = CommunityService::postComment($userId , $content , $postId);
        return $this->successResponse($submitResp);
    }

    public function createPost(CreatePostRequest $request){
        $userId = $request->user()->id;
        $form = $request->validated();

        $createdResp = CommunityService::createPost($userId , $form);
        return $this->successResponse(["post_id" => $createdResp]);
    }
}
