<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmWorkflowRequest;
use App\Http\Requests\CopilotPayload;
use App\Service\ProfileService;
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

    public function getProfileDetails(Request $request){
        try{
            $viewerId = auth()->id();
            $userId = (int) ($request->query('user_id') ?? $viewerId);

            $profileDetails = ProfileService::getProfileDetails(
                userId: $userId,
                viewerId: $viewerId
            );

            return $this->successResponse($profileDetails);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to get user profile" , ["1" => $ex->getMessage()]);
        }     
    }

    public function followUser(int $toBeFollowed){
        try{
            $userId = auth()->id();
            ProfileService::followUser($userId, $toBeFollowed);

            return $this->successResponse([] , "User followed successfully");
        }catch(Exception $ex){
            return $this->errorResponse("Failed to follow user" , ["1" => $ex->getMessage()]);
        }
    }

    public function isFollowed(int $toBeChecked){
        try{
            $userId = auth()->id();
            $response = ProfileService::isFollowingUser($toBeChecked , $userId);

            return $this->successResponse($response);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to check if followed by user" , ["1" => $ex->getMessage()]);
        }
    }

    public function getFriends(string $name){
        try{
            $userId = auth()->id();
            $suggestions = UserService::getFriends($name , $userId);
            return $this->successResponse($suggestions);
        }catch(Exception $ex){
            return $this->errorResponse("Failed to get friends" , ["1" => $ex->getMessage()]);
        }
    }
}
