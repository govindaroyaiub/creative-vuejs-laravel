<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Version;
use App\Models\FeedbackSet;

class SubVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_id',
        'name',
        'description',
        'is_active',
    ];

    public function version()
    {
        return $this->belongsTo(Version::class);
    }

    public function feedbackSets()
    {
        return $this->hasMany(FeedbackSet::class);
    }
}
