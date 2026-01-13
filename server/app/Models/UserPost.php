<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User as ModelsUser;

class UserPost extends Model{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'likes',
        'imports',
        'photo_url',
        'title',
        'description',
        'json_content'
    ];

    protected function casts():array{
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'json_content' => 'array'
        ];
    }

    public function user(){
        return $this->belongsTo(ModelsUser::class);
    }

    public function comments(){
        return $this->hasMany(PostComment::class , 'post_id', 'id');
    }
}
