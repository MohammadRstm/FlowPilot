<?php

namespace App\Http\Controllers;

use App\Models\UserCopilotHistory;
use App\Models\Message;
use App\Http\Controllers\Controller;
use App\Service\UserService;
use Exception;

class UserCopilotHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        try {
            $userId = 1; // TODO: replace with authenticated user id
            $histories = UserService::getChatHistory($userId);

            return $this->successResponse([
                'histories' => $histories,
            ]);
        } catch (Exception $ex) {
            return $this->errorResponse('Failed to fetch histories', ['1' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UserCopilotHistory $userCopilotHistory){
        try {
            $userId = 1; // TODO: replace with authenticated user id
            if ($userCopilotHistory->user_id !== $userId) {
                return $this->errorResponse('History not found', [], 404);
            }

            $userCopilotHistory->load(['messages' => function ($query) {
                $query->orderBy('created_at');
            }]);

            return $this->successResponse([
                'history' => $userCopilotHistory,
            ]);
        } catch (Exception $ex) {
            return $this->errorResponse('Failed to fetch history', ['1' => $ex->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserCopilotHistory $userCopilotHistory){
        try {
            $userId = 1; // TODO: replace with authenticated user id
            if ($userCopilotHistory->user_id !== $userId) {
                return $this->errorResponse('History not found', [], 404);
            }

            // Delete all messages for this history
            Message::where('history_id', $userCopilotHistory->id)->delete();

            // Delete the history itself
            $userCopilotHistory->delete();

            return $this->successResponse([], 'History deleted');
        } catch (Exception $ex) {
            return $this->errorResponse('Failed to delete history', ['1' => $ex->getMessage()]);
        }
    }
}
