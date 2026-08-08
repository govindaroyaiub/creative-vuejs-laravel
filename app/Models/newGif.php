<?php

namespace App\Models;

use App\Models\Concerns\ResolvesPreviewName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\newVersion;
use App\Models\BannerSize;
use App\Models\newPreview;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newGif extends Model
{
    use ResolvesPreviewName;
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Gif'; // name for this log

    protected $fillable = [
        'version_id',
        'name',
        'path',
        'size_id',
        'file_size',
        'position',
    ];

    public function version()
    {
        return $this->belongsTo(newVersion::class, 'version_id');
    }

    public function size()
    {
        return $this->belongsTo(BannerSize::class, 'size_id');
    }

    // Accessor to get preview name
    public function getPreviewNameAttribute()
    {
        return $this->previewNameVia(['version', 'feedbackset', 'feedback', 'category', 'preview']);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Gif of: ' . $this->preview_name);
    }
}
