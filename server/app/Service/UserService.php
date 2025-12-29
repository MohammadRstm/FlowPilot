<?php

namespace App\Service;

use App\Service\Copilot\GetAnswer;

class UserService{

    public static function getCopilotAnswer($question){
        $answer = GetAnswer::execute($question);
        return $answer;
    }
}
