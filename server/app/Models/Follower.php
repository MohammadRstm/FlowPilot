<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follower extends Model{
    use HasFactory;

    public $incrementing = false;
    protected $primaryKey = null;
    public $timestamps = false;


    protected $fillable = [
        'follower_id',
        'followed_id',
    ];

    protected function casts(): array{
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
