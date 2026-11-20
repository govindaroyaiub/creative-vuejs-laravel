<?php

namespace App\Models;

use App\Models\Concerns\ResolvesPreviewName;
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
    use ResolvesPreviewName;
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Social'; // name for this log

    /** Frames the client viewer can show a social image in (null = auto from size). */
    public const PLACEMENTS = ['feed', 'story', 'link', 'pin', 'storyboard', 'plain'];

    protected $fillable = [
        'version_id',
        'name',
        'path',
        'position',
        'width',
        'height',
        'placement',
    ];

    /**
     * Keep width/height in step with the file. Socials are written from
     * several places (single edit, bulk preview update), and every one of them
     * moves the file into place before saving the path, so reading it here
     * covers them all without touching each controller.
     */
    protected static function booted(): void
    {
        static::saving(function (newSocial $social) {
            if (!$social->isDirty('path') || !$social->path) {
                return;
            }
            $file = public_path($social->path);
            $info = is_file($file) ? @getimagesize($file) : false;
            $social->width = $info ? $info[0] : null;
            $social->height = $info ? $info[1] : null;
        });
    }

    public function version()
    {
        return $this->belongsTo(newVersion::class, 'version_id');
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
            ->useLogName('Social of: ' . $this->preview_name);
    }
}
