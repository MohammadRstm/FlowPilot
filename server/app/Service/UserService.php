<?php

namespace App\Service;

use App\Jobs\saveHistory;
use App\Models\UserCopilotHistory;
use App\Service\Copilot\GetAnswer;
use App\Service\Copilot\SaveWorkflow;

class UserService{

    public static function getCopilotAnswer(array $question){
        $answer = GetAnswer::execute($question);
         SaveHistory::dispatch(
            $question[0],
            $answer,
            1// user id static for now
        );
        return $answer;
    }

    public static function saveWorkflow($requestForm){
        $saved = SaveWorkflow::save($requestForm);
        return $saved; 
    }

    public static function saveCopilotHistories(
        string $question,
        string $answer,
        int $userId
    ) {
        UserCopilotHistory::create([
            'user_id' => $userId,
            'question' => $question,
            'response' => $answer
        ]);
    }

}
