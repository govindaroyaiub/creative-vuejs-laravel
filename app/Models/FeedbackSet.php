<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SubVersion;
use App\Models\SubBanner;
use App\Models\SubVideo;
use App\Models\SubSocial;
use App\Models\SubGif;

class FeedbackSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_version_id',
        'name'
    ];

    public function subVersion()
    {
        return $this->belongsTo(SubVersion::class);
    }

    public function subBanner()
    {
        return $this->hasMany(SubBanner::class);
    }

    public function subVideo()
    {
        return $this->hasMany(SubVideo::class);
    }

    public function subSocial()
    {
        return $this->hasMany(SubSocial::class);
    }

    public function subGif()
    {
        return $this->hasMany(SubGif::class);
    }

    public function banners()
    {
        return $this->hasMany(\App\Models\SubBanner::class);
    }

    public function videos()
    {
        return $this->hasMany(\App\Models\SubVideo::class);
    }

    public function socials()
    {
        return $this->hasMany(\App\Models\SubSocial::class);
    }

    public function gifs()
    {
        return $this->hasMany(\App\Models\SubGif::class);
    }
}
