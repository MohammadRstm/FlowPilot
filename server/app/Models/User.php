<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Http\Models\UserPost;
use App\Models\UserPost as ModelsUserPost;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'photo_url',
        'email_verified_at'
    ];

    protected function casts(): array{
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFullNameAttribute(): string{
        return "{$this->first_name} {$this->last_name}";
    }

    public function posts(){
        return $this->hasMany(ModelsUserPost::class);
    }

    public function copilotHistory(){
        return $this->hasMany(UserCopilotHistory::class , 'user_id' , 'id');
    }
}
