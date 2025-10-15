<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newVersion;
use App\Models\newPreview;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use App\Models\BannerSize;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newBanner extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Banner'; // name for this log

    protected $fillable = ['version_id', 'name', 'path', 'size_id', 'file_size', 'position'];

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
        return $this->version?->feedbackset?->feedback?->category?->preview?->name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Banner of: '. $this->preview_name);
    }
}
