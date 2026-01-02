<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::group(["prefix" => "v0.1"] , function(){
    Route::get('/ping', function () {
        return response()->json(['status' => 'ok']);
    });
    Route::group(["prefix"=>"copilot"] , function(){
        Route::post("/ask" , [UserController::class, "ask"]);
        Route::post("/satisfied", [UserController::class , "confirmWorkflow"]);

    });


});