<?php

namespace App\Jobs;

use App\Service\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class saveHistory implements ShouldQueue{
    use Queueable;

    public string $question;
    public string $answer;
    public int $userId;

    public function __construct(string $question, string $answer, int $userId){
        $this->question = $question;
        $this->answer = $answer;
        $this->userId = $userId;
    }
 
    public function handle(string $question , string $answer): void{
        UserService::saveCopilotHistories(
            $this->question,
            $this->answer,
            $this->userId
        );
    }
}
