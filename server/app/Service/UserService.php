<?php

namespace App\Service;

use App\Models\AiModel;
use App\Models\Message;
use App\Models\User;
use App\Models\UserCopilotHistory;
use App\Service\Copilot\GetAnswer;
use App\Service\Copilot\SaveWorkflow;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService{
    
    public static function getCopilotAnswer(array $messages, ?int $historyId = null , ?callable $stream = null): array{
        $userId = auth()->id();
        $answer = GetAnswer::execute($messages , $stream);
        $history = self::handleHistoryManagement($userId , $historyId , $messages , $answer);

        return [
            'answer' => $answer,
            'history_id' => $history->id,
        ];
    }

    private static function handleHistoryManagement(int $userId , ?int $historyId , array $messages , $answer){
        $history = self::saveHistory($historyId , $userId);

        self::saveCopilotHistories(
            $history->id,
            $messages,
            $answer,
            $userId,
            env('OPENAI_MODEL')
        );

        return $history;
    }

    private static function saveHistory($historyId , $userId){
        // ensure we have a history for this conversation
        if ($historyId) {
            return UserCopilotHistory::where('id', $historyId)
                ->where('user_id', $userId)
                ->first();
        } else {
            return UserCopilotHistory::create([
                'user_id' => $userId,
            ]);
        }
    }

    public static function saveWorkflow($requestForm){
        $saved = SaveWorkflow::save($requestForm);
        return $saved; 
    }

    public static function saveCopilotHistories(
        int $historyId,
        array $messages,
        mixed $answer,
        int $userId,
        ?string $aiModel = null,
    ): void{
        $history = UserCopilotHistory::where('id', $historyId)
            ->where('user_id', $userId)
            ->first();

        if (!$history) {// sanity measure
            $history = UserCopilotHistory::create([
                'user_id' => $userId,
            ]);
        }

        $lastUserMessage = collect($messages)
            ->reverse()
            ->first(fn ($m) => 
                (is_array($m) ? $m['type'] : $m->type) === 'user'
            );


        if (!$lastUserMessage) return;

        $userContent = is_array($lastUserMessage)
            ? ($lastUserMessage['content'] ?? null)
            : ($lastUserMessage->content ?? null);

        if (!$userContent) return;

        // get ai model 
        $model = AiModel::where("name" , $aiModel)
                ->first();
        $aiModelId = $model->id;
        
        $newMessage = new Message();
        $newMessage->history_id = $historyId;
        $newMessage->ai_model = $aiModelId;
        $newMessage->ai_response = $answer;
        $newMessage->user_message = $userContent;

        $newMessage->save();
    }

    public static function getChatHistory(int $userId){
        return UserCopilotHistory::with(['messages' => function ($query) {
                $query->orderBy('created_at');
            }])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public static function getFriends(string $name , int $userId){
        if(empty($name)){
            throw new Exception("Name is empty");
        }

        $parts = preg_split('/\s+/', $name);

        $query = User::query()
            ->where('id', '!=', $userId)

            // exclude already followed users
            ->whereNotIn('id', function ($q) use ($userId) {
                $q->select('followed_id')
                ->from('followers')
                ->where('follower_id', $userId);
            })

            ->where(function ($q) use ($name, $parts) {
                // full name contains
                $q->whereRaw(
                    "LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?",
                    ['%' . strtolower($name) . '%']
                );

                // first / last name partials
                foreach ($parts as $part) {
                    $q->orWhere('first_name', 'LIKE', "%{$part}%")
                    ->orWhere('last_name', 'LIKE', "%{$part}%");
                }
            })

            ->select([
                'id',
                'first_name',
                'last_name',
                'photo_url',
                DB::raw("CONCAT(first_name, ' ', last_name) AS full_name"),
            ])

            ->orderByRaw("
                CASE
                    WHEN LOWER(CONCAT(first_name, ' ', last_name)) LIKE ? THEN 1
                    WHEN LOWER(first_name) LIKE ? THEN 2
                    WHEN LOWER(last_name) LIKE ? THEN 3
                    ELSE 4
                END
            ", [
                strtolower($name) . '%',
                strtolower($name) . '%',
                strtolower($name) . '%',
            ])

            ->limit(10);

        return $query->get();
    }

    public static function getUserAccount(int $userId){
        $user = User::findorFail($userId);

        if($user->password && !$user->google_id || $user->password && $user->google_id){// normal account or both
            return[
                "normalAccount" => true,
                "googleAccount" => false
            ];
        }else if(!$user->password && $user->google_id){// google account
            return[
                "normalAccount" => false,
                "googleAccount" => true
            ];
        }else{// impossible 
            return null;
        }
    }

}
