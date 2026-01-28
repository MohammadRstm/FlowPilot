<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentPostRequest;
use App\Service\PostCommentService;
use Illuminate\Http\Request;

class PostCommentController extends AuthenticatedController{

    public function toggleCommentLike(int $commentId){
        $userId = $this->authUser->id;

        $likedResp = PostCommentService::toggleCommentLike($userId , $commentId);
        return $this->successResponse($likedResp);
    }

    public function getPostComments(int $postId){
        $comments = PostCommentService::getComments($postId);
        return $this->successResponse($comments);
    }

    public function postComment($postId , CommentPostRequest $request){
        $content = $request->validated()["content"];

        $submitResp = PostCommentService::postComment($this->authUser->id, $content , $postId);
        return $this->successResponse($submitResp);
    }
  
}
