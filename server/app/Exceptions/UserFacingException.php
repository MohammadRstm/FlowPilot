<?php

namespace App\Exceptions;

use Exception;

class UserFacingException extends Exception{

    protected int $status;

    public function __construct(string $message, int $status = 400){
        parent::__construct($message);
        $this->status = $status;
    }

    public function getStatus(): int{
        return $this->status;
    }
    
}
