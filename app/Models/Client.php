<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'website',
        'preview_url',
        'logo',
        'brand_color',
    ];
}
