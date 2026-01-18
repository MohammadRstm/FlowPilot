<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserCopilotHistoryController;
use Illuminate\Support\Facades\Route;


Route::group(["prefix" => "v0.1"] , function(){

    Route::group(["prefix" => "auth"] , function(){
        Route::post('/google', [AuthController::class, 'googleLogin']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/me', [AuthController::class, 'me'])->middleware('jwt.auth');
        Route::put("/setPassword" , [AuthController::class , 'setPassword'])->middleware('jwt.auth');
    });

    Route::group(["prefix"=>"copilot" , "middlware" => "jwt.auth"] , function(){
        Route::post("/ask" , [UserController::class, "ask"]);
        Route::get("/ask-stream" , [UserController::class , "askStream"]);
        Route::post("/satisfied", [UserController::class , "confirmWorkflow"]);
        Route::get('/histories', [UserCopilotHistoryController::class, 'index']);
        Route::get('/histories/{userCopilotHistory}', [UserCopilotHistoryController::class, 'show']);
        Route::delete('/histories/{userCopilotHistory}', [UserCopilotHistoryController::class, 'destroy']);
    });

    Route::get("/profileDetails" , [UserController::class , "getProfileDetails"])->middleware("jwt.auth");
    Route::get('/histories/{history}/download',[UserCopilotHistoryController::class, 'download'])->name('user.histories.download')->middleware('jwt.auth');

});
