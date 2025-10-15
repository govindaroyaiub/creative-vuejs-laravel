<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newCategory;
use App\Models\newFeedbackSet;
use App\Models\newPreview;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newFeedback extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'new_feedback';

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'is_active',
        'is_approved',
    ];

    public function category()
    {
        return $this->belongsTo(newCategory::class, 'category_id');
    }

    public function feedbackSets()
    {
        return $this->hasMany(newFeedbackSet::class, 'feedback_id');
    }

    // Direct relationship to preview
    public function preview()
    {
        return $this->hasOneThrough(
            newPreview::class,
            newCategory::class,
            'id',
            'id',
            'category_id',
            'preview_id'
        );
    }

    // Accessor for preview name
    public function getPreviewNameAttribute()
    {
        return $this->category?->preview?->name;
    }

    protected static $logAttributes = ['*'];
    protected static $logName = 'Feedback';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Feedback of: '. $this->preview_name);
    }
}
