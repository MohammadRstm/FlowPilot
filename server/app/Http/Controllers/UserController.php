<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmWorkflowRequest;
use App\Http\Requests\CopilotPayload;
use App\Service\UserService;
use Exception;
use Illuminate\Support\Facades\Log;

class UserController extends Controller{
    public function ask(CopilotPayload $req){
        try{
            $payload = $req->validated();
            $messages = $payload['messages'];
            $historyId = $payload['history_id'] ?? null;

            $result = UserService::getCopilotAnswer($messages, $historyId);

            $answer = $result['answer'];
            $historyId = $result['history_id'];

            if (is_string($answer)) {
                $decoded = json_decode($answer, true);
                $response = $decoded === null ? $answer : $decoded;
            } else {
                $response = $answer;
            }

            return $this->successResponse([
                'answer' => $response,
                'historyId' => $historyId,
            ]);
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
