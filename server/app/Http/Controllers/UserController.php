<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmWorkflowRequest;
use App\Http\Requests\CopilotPayload;
use App\Service\UserService;
use Exception;

class UserController extends Controller{
    public function ask(CopilotPayload $req){
        try{
            $answer = UserService::getCopilotAnswer($req["question"]);
            if (is_string($answer)) {
                $decoded = json_decode($answer, true);
                $response = $decoded === null ? $answer : $decoded;
            } else {
                $response = $answer;
            }

            return $this->successResponse(["answer" => $response]);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to ask copilot" , ["1" => $ex->getMessage()]);
        }
    }
    public function confirmWorkflow(ConfirmWorkflowRequest $req){
        try{
            UserService::saveWorkflow($req);
            return $this->successResponse(["message" => "Workflow saved"]);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to save worfklow" , ["1" => $ex->getMessage()]);
        }
    }
}
