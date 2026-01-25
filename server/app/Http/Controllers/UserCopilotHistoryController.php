<?php

namespace App\Http\Controllers;

use App\Models\UserCopilotHistory;
use App\Models\Message;
use App\Http\Controllers\Controller;
use App\Service\UserService;
use Illuminate\Http\Request;

class UserCopilotHistoryController extends Controller{
  
    public function index(Request $request){
        $userId = $request->user()->id; 
        $histories = UserService::getChatHistory($userId);

        return $this->successResponse([
            'histories' => $histories,
        ]);
    }

    public function show(Request $request , UserCopilotHistory $userCopilotHistory){
        $userId = $request->user()->id;  
        if ($userCopilotHistory->user_id !== $userId) {
            return $this->errorResponse('History not found', [], 404);
        }

        $userCopilotHistory->load(['messages' => function ($query) {
            $query->orderBy('created_at');
        }]);

        return $this->successResponse([
            'history' => $userCopilotHistory,
        ]);
    }

    public function destroy(Request $request , UserCopilotHistory $userCopilotHistory){
        $userId = $request->user()->id; 
        if ($userCopilotHistory->user_id !== $userId) {
            return $this->errorResponse('History not found', [], 404);
        }

        Message::where('history_id', $userCopilotHistory->id)->delete();

        $userCopilotHistory->delete();

        return $this->successResponse([], 'History deleted');
    }

    public function download(Request $request , UserCopilotHistory $history){
        if ($history->user_id !== $request->user()->id){
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
