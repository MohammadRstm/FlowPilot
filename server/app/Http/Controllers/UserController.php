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
use Illuminate\Support\Facades\Log;

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
        UserService::saveWorkflow($req);
        return $this->successResponse(["message" => "Workflow saved"]);
    }

    public function getProfileDetails(Request $request){ 
        $viewerId = $request->user()->id;// user viewing the profile (who made the request)
        $userId = (int) ($request->query('user_id') ?? $viewerId);// user being viewed

        $profileDetails = ProfileService::getProfileDetails(
            userId: $userId,
            viewerId: $viewerId
        );
        return $this->successResponse($profileDetails);
    }

    public function followUser(Request $request , int $toBeFollowed){ 
        $userId = $request->user()->id;

        ProfileService::toggeleFollow($userId, $toBeFollowed);
        return $this->successResponse([] , "User followed successfully");
    }

    public function isFollowed(Request $request , int $toBeChecked){ 
        $userId = $request->user()->id;

        $response = ProfileService::isFollowingUser($toBeChecked , $userId);
        return $this->successResponse($response);
    }

    public function getFriends(Request $request , string $name){
        $userId = $request->user()->id;

        $suggestions = UserService::getFriends($name , $userId);
        return $this->successResponse($suggestions);
    }

    public function uploadAvatar(AvatarUploadRequest $request){
        $user = $request->user();
        $avatar = $request->file("avatar");

        ProfileService::uploadFile($user , $avatar);
        return $this->successResponse([] , "uploaded successfully");
    }

    public function getUserAccount(Request $request){
        $userId = $request->user()->id;

        $userAccountInfo = UserService::getUserAccount($userId);
        return $this->successResponse($userAccountInfo);
    }
}
