<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ColorPalette;
use App\Models\User;

class Client extends Model
{
    protected $fillable = [
        'name',
        'website',
        'preview_url',
        'logo',
        'color_palette_id',
    ];

    public function colorPalette()
    {
        return $this->belongsTo(ColorPalette::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
