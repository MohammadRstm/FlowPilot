<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class AuthenticatedController extends Controller{
    
    protected Authenticatable $authUser;

    public function __construct(){
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $this->authUser = $request->user();
            return $next($request);
        });
    }
}
