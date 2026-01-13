<?php

namespace App\Service;

use App\Models\AiModel;
use App\Models\Message;
use App\Models\UserCopilotHistory;
use App\Service\Copilot\GetAnswer;
use App\Service\Copilot\SaveWorkflow;
use Illuminate\Support\Facades\Log;

class UserService{

    public static function getCopilotAnswer(array $messages, ?int $historyId = null , ?callable $stream = null): array{
        $userId = 1; // replace with authenticated user id when auth is wired

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
