<?php

namespace App\Service;

use App\Service\Copilot\GetAnswer;

class UserService{

    public static function getCopilotAnswer($question , $user){
        $answer = GetAnswer::execute($question , $user);
        return $answer;
    }
}
