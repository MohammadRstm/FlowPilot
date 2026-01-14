<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmWorkflowRequest;
use App\Http\Requests\CopilotPayload;
use App\Service\UserService;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller{
    public function askStream(Request $req){
        return response()->stream(function () use ($req){// we are telling laravel that we're sending chunks of data not everything at once

            $messages = json_decode($req->query('messages'), true);
            $historyId = $req->query('history_id');

            if (!$messages || !is_array($messages)) {
                abort(400, "Invalid messages payload");
            }

            $stream = UserService::initializeStream();

            $result = UserService::getCopilotAnswer(
                $messages,
                $historyId,
                $stream// the helper is sent further down the pipeline for detialed chunks
            );

            // finally we send the results
            UserService::returnFinalWorkflowResult($result);

        }, 200, UserService::returnSseHeaders());
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
