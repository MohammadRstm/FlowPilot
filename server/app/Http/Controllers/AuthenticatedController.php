<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthenticatedController extends Controller{
    
    protected Authenticatable $authUser;

    public function __construct(){
        $this->middleware('jwt.auth');

        $this->middleware(function ($request, $next) {
            $this->authUser = $request->user();
            return $next($request);
        });
    }
}
