<?php

namespace App\Service;

use App\Service\Copilot\GetAnswer;
use App\Service\Copilot\SaveWorkflow;

class UserService{

    public static function getCopilotAnswer(array $question){
        $answer = GetAnswer::execute($question);
        return $answer;
    }

    public static function saveWorkflow($requestForm){
        $saved = SaveWorkflow::save($requestForm);
        return $saved; 
    }
}
