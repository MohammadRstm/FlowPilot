<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentPostRequest;
use App\Service\PostCommentService;
use Illuminate\Http\Request;

class PostCommentController extends Controller{

    public function toggleCommentLike(Request $request , int $commentId){
        $userId = $request->user()->id;

        $likedResp = PostCommentService::toggleCommentLike($userId , $commentId);
        return $this->successResponse($likedResp);
    }

    public function getPostComments(int $postId){
        $comments = PostCommentService::getComments($postId);
        return $this->successResponse($comments);
    }

    public function postComment($postId , CommentPostRequest $request){
        $content = $request->validated()["content"];
        $userId = $request->user()->id;

        $submitResp = PostCommentService::postComment($userId , $content , $postId);
        return $this->successResponse($submitResp);
    }
  
}
