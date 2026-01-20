<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserCopilotHistoryController;
use Illuminate\Support\Facades\Route;
use phpseclib3\Crypt\EC\Formats\Keys\Common;

Route::group(["prefix" => "v0.1"] , function(){
    
    Route::post('/google', [AuthController::class, 'googleLogin']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::group(["prefix" => "auth", "middleware" => "jwt.auth"] , function(){
        Route::get('/me', [AuthController::class, 'me']);
        Route::put("/setPassword" , [AuthController::class , 'setPassword']);


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
            Route::post("/follow/{userId}" , [UserController::class] , "followUser");
            Route::get("/isFollowed/{userId}" , [UserController::class] , "isFollowed");
        });

        Route::group(["prefix" => "community"] , function(){
            Route::get("/posts" , [CommunityController::class , "fetchPosts"]);
            Route::post("/toggleLike/{postId}" , [CommunityController::class , "toggleLike"]);
            Route::get("/export/{postId}" , [CommunityController::class , "export"]);

            Route::get("/comments/{postId}" , [CommunityController::class , "getPostComments"]);
            Route::post("/submitComment/{postId}" , [CommunityController::class , "postComment"]);
            Route::post("/toggelCommentLike/{commentId}" , [CommunityController::class , "toggleCommentLike"]);
        });
    });


});
