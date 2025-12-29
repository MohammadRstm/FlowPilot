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
            $user = [
                "id" => 1,
                "n8n_url" => "",
                "n8n_api_key" => ""
            ];
            $answer = UserService::getCopilotAnswer($req["question"], $user);
            return $this->successResponse($answer ?? []);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to ask copilot" , ["1" => $ex->getMessage()]);
        }
    }
}
