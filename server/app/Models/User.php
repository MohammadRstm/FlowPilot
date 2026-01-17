<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\UserPost as ModelsUserPost;

class User extends Authenticatable{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_role_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'photo_url',
        'google_id',
        'email_verified_at',
    ];

    protected function casts(): array{
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* RELATIONSHIPS */

    public function role(){
        return $this->belongsTo(UserRole::class);
    }

    public function posts(){
        return $this->hasMany(ModelsUserPost::class);
    }

    public function copilotHistory(){
        return $this->hasMany(UserCopilotHistory::class , 'user_id' , 'id');
    }

    public function comments(){
        return $this->hasMany(PostComment::class , 'user_id' , 'id');
    }

    public function followings(){
        return $this->belongsToMany(
            User::class,     
            'followers',    
            'follower_id',  
            'followed_id' 
        );
    }

    public function followers(){
        return $this->belongsToMany(
            User::class,
            'followers',
            'followed_id',  
            'follower_id'  
        );
    }

    /* HELPERS */

    public function getFullNameAttribute(): string{
        return "{$this->first_name} {$this->last_name}";
    }
}
