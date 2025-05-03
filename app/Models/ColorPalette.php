<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client;

class ColorPalette extends Model
{
    protected $fillable = [
        'name',
        'primary',
        'secondary',
        'tertiary',
        'quaternary',
        'status',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
