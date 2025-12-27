<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCopilotHistory extends Model{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'question',
        'response',
        'ai_description',
        'ai_model',
    ];

    protected function casts():array{
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'response' => 'array'
        ];
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
