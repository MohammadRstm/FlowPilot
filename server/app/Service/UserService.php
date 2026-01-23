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

    public static function getCopilotAnswer(array $messages, ?int $userId , ?int $historyId = null , ?callable $stream = null): array{

        $answer = GetAnswer::execute($messages , $stream);
        if(!$answer) throw new Exception("Failed to generate n8n workflow");
        $history = self::handleHistoryManagement($userId , $historyId , $messages , $answer);

        return [
            'answer' => $answer,
            'history_id' => $history->id,
        ];
    }

    private static function handleHistoryManagement(?int $userId , ?int $historyId , array $messages , $answer){
        Log::debug("here");
        $history = self::saveHistory($historyId , $userId);
        Log::debug("here");

        self::saveCopilotHistories(
            $history->id,
            $messages,
            $answer,
            $userId,
            env('OPENAI_MODEL')
        );
        Log::debug("here");

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
    // CLEAN
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

        $lastUserMessage = collect($messages)->last();
        if (!isset($lastUserMessage['content'])){
            throw new Exception("No message content");
        };

        $userContent = $lastUserMessage['content'];

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

    public static function getUserAccount(int $userId): array{
        $user = User::findOrFail($userId);

        return [
            "normalAccount" => (bool) $user->password,
            "googleAccount" => (bool) $user->google_id,
        ];
    }

    public static function returnSseHeaders(){
        return [
            "Content-Type" => "text/event-stream",
            "Cache-Control" => "no-cache",
            "Connection" => "keep-alive",
            "X-Accel-Buffering" => "no",
        ];
    }

    public static function returnFinalWorkflowResult($result){
        echo "event: result\n";
        echo "data: " . json_encode($result) . "\n\n";
        ob_flush(); flush();
    }

    public static function initializeStream(){
        return function (string $event, $data){// stream helper, this sends the events (chunks) to frontend
                if (!is_string($data)) {
                    $data = json_encode($data);
                }

                echo "event: $event\n";
                echo "data: $data\n\n";// needs to have two new line charachters or else it breaks 
                ob_flush(); flush();// this forces laravel to send now instead of waiting
            };
    }

}
