<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostsLike extends Model
{
    protected $table = 'post_likes';
    protected $fillable = ['post_id', 'user_id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

}
