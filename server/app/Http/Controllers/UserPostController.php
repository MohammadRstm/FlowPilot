<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePostRequest;
use App\Service\UserPostService;
use Illuminate\Http\Request;

class UserPostController extends AuthenticatedController{

    public function fetchPosts(Request $request){
        $page = (int) $request->query('page', 1);

        $paginatedPosts = UserPostService::getPosts($this->authUser->id , $page);
        return $this->successResponse($paginatedPosts);
    }

    public function toggleLike(int $postId){

        $likeResp = UserPostService::toggleLike($this->authUser->id , $postId);
        return $this->successResponse($likeResp);
    }

    public function export(int $postId){

        $exportResp = UserPostService::export($this->authUser->id , $postId);
        return $this->successResponse($exportResp);
    }

    public function createPost(CreatePostRequest $request){
        $form = $request->validated();

        $createdResp = UserPostService::createPost($this->authUser->id , $form);
        return $this->successResponse(["post_id" => $createdResp]);
    }   
}
