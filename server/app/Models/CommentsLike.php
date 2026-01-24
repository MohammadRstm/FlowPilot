<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentsLike extends Model
{
    protected $table = 'comment_likes';
    protected $fillable = ['comment_id', 'user_id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
