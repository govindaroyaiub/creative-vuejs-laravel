<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newFeedbackSet;
use App\Models\newBanner;
use App\Models\newVideo;
use App\Models\newSocial;
use App\Models\newGif;

class newVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'feedback_set_id',
        'name'
    ];

    public function feedbackset()
    {
        return $this->belongsTo(newFeedbackSet::class, 'feedback_set_id');
    }

    public function banners()
    {
        return $this->hasMany(newBanner::class, 'version_id')->orderBy('position');
    }
    public function videos()
    {
        return $this->hasMany(newVideo::class,  'version_id')->orderBy('position');
    }
    public function gifs()
    {
        return $this->hasMany(newGif::class,    'version_id')->orderBy('position');
    }
    public function socials()
    {
        return $this->hasMany(newSocial::class, 'version_id')->orderBy('position');
    }
}
