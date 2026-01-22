<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserCopilotHistoryController;
use App\Http\Controllers\UserPostController;
use App\Service\UserService;
use Illuminate\Support\Facades\Route;

Route::group(["prefix" => "v0.1"] , function(){
    
    Route::post('/google', [AuthController::class, 'googleLogin']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::group(["prefix" => "auth", "middleware" => "jwt.auth"] , function(){
        
        Route::get('/me', [AuthController::class, 'me']);
        Route::post("/setPassword" , [AuthController::class , 'setPassword']);
        Route::get("/account" , [UserController::class , "getUserAccount"]);
        Route::put("/unlinkGoogleAccount" , [AuthController::class , "unlinkGoogleAccount"]);
        Route::post("/linkN8nAccount" , [AuthController::class , "linkN8nAccount"]);

        Route::group(["prefix"=>"copilot"] , function(){
            Route::post("/ask" , [UserController::class, "ask"]);
            Route::get("/ask-stream" , [UserController::class , "askStream"]);
            Route::post("/satisfied", [UserController::class , "confirmWorkflow"]);
            Route::get('/histories', [UserCopilotHistoryController::class, 'index']);
            Route::get('/histories/{userCopilotHistory}', [UserCopilotHistoryController::class, 'show']);
            Route::delete('/histories/{userCopilotHistory}', [UserCopilotHistoryController::class, 'destroy']);
        });

        Route::group(["prefix" => "profile"] , function(){
            Route::get('/histories/{history}/download',[UserCopilotHistoryController::class, 'download'])->name('user.histories.download');
            Route::get("/profileDetails" , [UserController::class , "getProfileDetails"]);
            Route::post("/follow/{toBeFollowed}" , [UserController::class, "followUser"]);
            Route::get("/isFollowed/{userId}" , [UserController::class , "isFollowed"]);
            Route::get("/searchFriends/{name}" , [UserController::class , "getFriends"]);
            Route::post("/avatar" , [UserController::class , "uploadAvatar"]);
        });

        Route::group(["prefix" => "community"] , function(){
            Route::get("/posts" , [UserPostController::class , "fetchPosts"]);
            Route::post("/toggleLike/{postId}" , [UserPostController::class , "toggleLike"]);
            Route::get("/export/{postId}" , [UserPostController::class , "export"]);

            Route::get("/comments/{postId}" , [UserPostController::class , "getPostComments"]);
            Route::post("/postComment/{postId}" , [UserPostController::class , "postComment"]);
            Route::post("/toggleCommentLike/{commentId}" , [UserPostController::class , "toggleCommentLike"]);
            Route::post("/createPost" , [UserPostController::class , "createPost"]);
        });
    });


});
