<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newFeedbackSet;
use App\Models\newBanner;

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
        return $this->hasMany(newBanner::class, 'version_id');
    }

    public function videos(){

    }

    public function socials(){

    }

    public function gifs(){
        
    }
}
