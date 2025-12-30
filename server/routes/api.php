<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::group(["prefix" => "v0.1"] , function(){
    Route::post("/ask" , [UserController::class, "ask"]);
    Route::get('/ping', function () {
        return response()->json(['status' => 'ok']);
    });

});