<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserCopilotHistoryController;
use App\Http\Controllers\UserPostController;
use Illuminate\Support\Facades\Route;

Route::group(["prefix" => "v0.1"] , function(){
    
    Route::post('/google', [AuthController::class, 'googleLogin']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get("/ask-stream" , [UserController::class , "askStream"]);

    Route::group(["prefix" => "auth", "middleware" => "jwt.auth"] , function(){

        Route::get('/me', [AuthController::class, 'me']);
        Route::post("/setPassword" , [AuthController::class , 'setPassword']);
        Route::get("/account" , [AuthController::class , "getUserAccount"]);
        Route::put("/unlinkGoogleAccount" , [AuthController::class , "unlinkGoogleAccount"]);
        Route::post("/linkN8nAccount" , [AuthController::class , "linkN8nAccount"]);

        Route::group(["prefix"=>"copilot"] , function(){
            Route::post("/satisfied", [UserController::class , "confirmWorkflow"]);
            Route::get('/histories', [UserCopilotHistoryController::class, 'index']);
            Route::get('/histories/{userCopilotHistory}', [UserCopilotHistoryController::class, 'show']);
            Route::delete('/histories/{userCopilotHistory}', [UserCopilotHistoryController::class, 'destroy']);
        });

        Route::group(["prefix" => "profile"] , function(){
            Route::get('/histories/{history}/download',[UserCopilotHistoryController::class, 'download'])->name('user.histories.download');
            
            Route::get("/profileDetails" , [ProfileController::class , "getProfileDetails"]);
            Route::post("/avatar" , [ProfileController::class , "uploadAvatar"]);
            Route::get("/searchFriends/{name}" , [ProfileController::class , "getFriends"]);

            Route::post("/follow/{toBeFollowed}" , [FollowerController::class, "followUser"]);
            Route::get("/isFollowed/{userId}" , [FollowerController::class , "isFollowed"]);
        });

        Route::group(["prefix" => "community"] , function(){
            Route::get("/posts" , [UserPostController::class , "fetchPosts"]);
            Route::post("/toggleLike/{postId}" , [UserPostController::class , "toggleLike"]);
            Route::get("/export/{postId}" , [UserPostController::class , "export"]);
            Route::post("/createPost" , [UserPostController::class , "createPost"]);

            Route::get("/comments/{postId}" , [PostCommentController::class , "getPostComments"]);
            Route::post("/postComment/{postId}" , [PostCommentController::class , "postComment"]);
            Route::post("/toggleCommentLike/{commentId}" , [PostCommentController::class , "toggleCommentLike"]);
        });
    });


});
