<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmWorkflowRequest;
use App\Http\Requests\CopilotPayload;
use App\Service\UserService;
use Exception;
use Illuminate\Http\Request;
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

    public function askStream(Request $req){
        return response()->stream(function () use ($req){// we are telling laravel that we're sending chunks of data not everything at once

            $messages = json_decode($req->query('messages'), true);
            $historyId = $req->query('history_id');

            if (!$messages || !is_array($messages)) {
                abort(400, "Invalid messages payload");
            }


            $stream = function (string $event, $data){// stream helper, this sends the events (chunks) to frontend
                if (!is_string($data)) {
                    $data = json_encode($data);
                }

                echo "event: $event\n";
                echo "data: $data\n\n";// needs to have two new line charachters or else it breaks 
                ob_flush(); flush();// this forces laravel to send now instead of waiting
            };


            $result = UserService::getCopilotAnswer(
                $messages,
                $historyId,
                $stream// the helper is sent further down the pipeline for detialed chunks
            );

            // finally we send the results
            echo "event: result\n";
            echo "data: " . json_encode($result) . "\n\n";
            ob_flush(); flush();

        }, 200, [
            "Content-Type" => "text/event-stream",
            "Cache-Control" => "no-cache",
            "Connection" => "keep-alive",
            "X-Accel-Buffering" => "no",
        ]);
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
