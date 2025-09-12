<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tetris extends Model
{
    protected $fillable = ['user_id', 'score'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
