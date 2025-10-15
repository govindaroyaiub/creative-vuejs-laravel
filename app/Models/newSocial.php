<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\newVersion;
use App\Models\newPreview;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newSocial extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Social'; // name for this log

    protected $fillable = [
        'version_id',
        'name',
        'path',
        'position',
    ];

    public function version()
    {
        return $this->belongsTo(newVersion::class, 'version_id');
    }

    // Accessor to get preview name
    public function getPreviewNameAttribute()
    {
        return $this->version?->feedbackset?->feedback?->category?->preview?->name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Social of: ' . $this->preview_name);
    }
}
