<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCopilotHistory extends Model{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
    ];

    protected function casts():array{
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function messages(){
        return $this->hasMany(Message::class, 'history_id');
    }
}
