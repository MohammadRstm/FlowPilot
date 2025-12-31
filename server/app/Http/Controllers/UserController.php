<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CopilotPayload;
use App\Service\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller{
    public function ask(CopilotPayload $req){
        try{
            // test run without validation
            $user = [
                "id" => 1,
                "n8n_url" => null,
                "n8n_api_key" => null
            ];
            $answer = UserService::getCopilotAnswer($req["question"], $user);
            return $this->successResponse(["answer" => json_decode($answer)]);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to ask copilot" , ["1" => $ex->getMessage()]);
        }
    }
}
