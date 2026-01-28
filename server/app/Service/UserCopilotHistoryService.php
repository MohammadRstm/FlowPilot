<?php

namespace App\Service;

use App\Models\Message;
use App\Models\UserCopilotHistory;
use Exception;
use Illuminate\Database\Eloquent\Model;

class UserCopilotHistoryService{

   public static function getUserHistories(int $userId){
       return UserCopilotHistory::with(['messages' => function ($query) {
                $query->orderBy('created_at');
            }])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
   }

    public static function getUserCopilotHistoryDetials(int $userId , Model $userCopilotHistory){
        if($userCopilotHistory->user_id !== $userId){
            throw new Exception('History not found');
        }

        $userCopilotHistory->load(['messages' => function ($query) {
            $query->orderBy('created_at');
        }]);

        return $userCopilotHistory;
    }

    public static function deleteHistory(int $userId, Model $userCopilotHistory){
        if ($userCopilotHistory->user_id !== $userId) {
            throw new Exception('History not found');
        }

        Message::where('history_id', $userCopilotHistory->id)->delete();

        $userCopilotHistory->delete();
    }

    public static function getDownloadableContent(int $userId , Model $history){


        $lastMessage = $history->messages()
            ->latest('created_at')
            ->first();

        if (!$lastMessage || !$lastMessage->ai_response) {
            abort(404, 'No AI response found');
        }

        return $lastMessage;
    }

}
