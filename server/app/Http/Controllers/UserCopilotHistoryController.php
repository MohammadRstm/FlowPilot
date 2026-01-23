<?php

namespace App\Http\Controllers;

use App\Models\UserCopilotHistory;
use App\Models\Message;
use App\Http\Controllers\Controller;
use App\Service\UserService;
use Exception;
use Illuminate\Support\Facades\Log;

class UserCopilotHistoryController extends Controller{
  
    public function index(){
        try {
            $userId = 1; 
            $histories = UserService::getChatHistory($userId);

            return $this->successResponse([
                'histories' => $histories,
            ]);
        } catch (Exception $ex) {
            return $this->errorResponse('Failed to fetch histories', ['1' => $ex->getMessage()]);
        }
    }

    public function show(UserCopilotHistory $userCopilotHistory){
        try {
            $userId = auth()->id(); 
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

    public function download(UserCopilotHistory $history){
        if ($history->user_id !== auth()->id()){
            abort(403); 
        }

        $lastMessage = $history->messages()
            ->latest('created_at')
            ->first();

        if (!$lastMessage || !$lastMessage->ai_response) {
            abort(404, 'No AI response found');
        }

        return response()->json(
            $lastMessage->ai_response,
            200,
            [
                'Content-Disposition' => 'attachment; filename="history.json"',
            ]
        );
    }
}
