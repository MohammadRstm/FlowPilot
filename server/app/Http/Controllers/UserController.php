<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvatarUploadRequest;
use App\Http\Requests\ConfirmWorkflowRequest;
use App\Http\Requests\CopilotPayload;
use App\Service\ProfileService;
use App\Service\UserService;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller{
    
    public function askStream(Request $req){
        return response()->stream(function () use ($req){// initiate stream
            $this->askCopilot($req);
        }, 200, UserService::returnSseHeaders());
    }

    public function confirmWorkflow(ConfirmWorkflowRequest $req){  
        UserService::saveWorkflow($req);
        return $this->successResponse(["message" => "Workflow saved"]);
    }

    private function askCopilot($req){
        $userId = (int) $req->query("userId");
        $messages = json_decode($req->query('messages'), true);
        $historyId = (int) $req->query('history_id');

        if ((!$messages || !is_array($messages)) || !$userId) {
            abort(400, "Invalid payload");
        }

        $stream = UserService::initializeStream();

        $result = UserService::getCopilotAnswer(
            $messages,
            $userId,
            $historyId,
            $stream
        );

        UserService::returnFinalWorkflowResult($result);
    }


}
