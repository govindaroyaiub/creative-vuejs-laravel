<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newBanner;
use App\Models\newGif;

class BannerSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'width',
        'height',
    ];

    public function banners()
    {
        return $this->hasMany(newBanner::class, 'size_id');
    }
    public function gifs()
    {
        return $this->hasMany(newGif::class, 'size_id');
    }
}
