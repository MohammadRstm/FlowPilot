<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model{
    protected $fillable = [
        'name',
    ];

    protected function casts(): array{
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
