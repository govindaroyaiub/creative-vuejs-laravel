<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Version;
use App\Models\SubBanner;
use App\Models\SubVideo;
use App\Models\SubSocial;
use App\Models\SubGif;

class SubVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_id',
        'name',
        'is_active',
    ];

    public function version()
    {
        return $this->belongsTo(Version::class);
    }

    public function subBanner()
    {
        return $this->hasOne(SubBanner::class);
    }

    public function subVideo()
    {
        return $this->hasOne(SubVideo::class);
    }

    public function subSocial()
    {
        return $this->hasOne(SubSocial::class);
    }

    public function subGif()
    {
        return $this->hasOne(SubGif::class);
    }
}
