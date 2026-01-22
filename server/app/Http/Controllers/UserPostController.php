<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
class UserPostController extends Controller{

    public function fetchPosts(Request $request){
        $userId = $request->user()->id;
        $page = (int) $request->query('page', 1);

        $paginatedPosts = CommunityService::getPosts($userId , $page);
        return $this->successResponse($paginatedPosts);
    }
    

   
}
