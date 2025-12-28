<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CopilotPayload;
use App\Service\UserService;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller{
    public function ask(CopilotPayload $req){
        try{
            $answer = UserService::getCopilotAnswer($req["question"]);
            return $this->successResponse($answer ?? []);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to ask copilot" , ["1" => $ex->getMessage()]);
        }
    }
}
