<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentPostRequest;
use App\Http\Requests\CreatePostRequest;
use App\Service\UserPostService;
use Illuminate\Http\Request;
class UserPostController extends Controller{

    public function fetchPosts(Request $request){
        $userId = $request->user()->id;
        $page = (int) $request->query('page', 1);

        $paginatedPosts = UserPostService::getPosts($userId , $page);
        return $this->successResponse($paginatedPosts);
    }

      public function toggleLike(Request $request , int $postId){
        $userId = $request->user()->id;

        $likeResp = UserPostService::toggleLike($userId , $postId);
        return $this->successResponse($likeResp);
    }

    public function export(Request $request , int $postId){
        $userId = $request->user()->id;

        $exportResp = UserPostService::export($userId , $postId);
        return $this->successResponse($exportResp);
    }

    public function toggleCommentLike(Request $request , int $commentId){
        $userId = $request->user()->id;

        $likedResp = UserPostService::toggleCommentLike($userId , $commentId);
        return $this->successResponse($likedResp);
    }

    public function getPostComments(int $postId){
        $comments = UserPostService::getComments($postId);
        return $this->successResponse($comments);
    }

    public function postComment($postId , CommentPostRequest $request){
        $content = $request->validated()["content"];
        $userId = $request->user()->id;

        $submitResp = UserPostService::postComment($userId , $content , $postId);
        return $this->successResponse($submitResp);
    }

    public function createPost(CreatePostRequest $request){
        $userId = $request->user()->id;
        $form = $request->validated();

        $createdResp = UserPostService::createPost($userId , $form);
        return $this->successResponse(["post_id" => $createdResp]);
    }   
}
