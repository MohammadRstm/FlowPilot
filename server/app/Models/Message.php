<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model{
    protected $table = "messages";

    protected $fillable = [
        'history_id',
        'ai_response',
        'user_message',
        'ai_model'
    ];
    
    protected $casts = [
        'ai_response' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function history(){
        return $this->belongsTo(UserCopilotHistory::class, 'history_id');
    }
}
