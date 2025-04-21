<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'width',
        'height',
    ];
}