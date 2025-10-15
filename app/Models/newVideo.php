<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\newVersion;
use App\Models\VideoSize;
use App\Models\newPreview;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newVideo extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Video'; // name for this log

    protected $fillable = [
        'version_id',
        'name',
        'path',
        'size_id',
        'codec',
        'aspect_ratio',
        'fps',
        'file_size',
        'companion_banner_path',
        'position',
    ];

    public function version()
    {
        return $this->belongsTo(newVersion::class, 'version_id');
    }

    public function size()
    {
        return $this->belongsTo(VideoSize::class, 'size_id');
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
            ->useLogName('Video of: ' . $this->preview_name);
    }
}
