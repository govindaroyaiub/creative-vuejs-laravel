<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newFeedback;
use App\Models\newCategory;
use App\Models\newPreview;
use App\Models\newVersion;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newFeedbackSet extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'feedback_id',
        'name'
    ];

    public function feedback()
    {
        return $this->belongsTo(newFeedback::class, 'feedback_id');
    }

    public function versions()
    {
        return $this->hasMany(newVersion::class, 'feedback_set_id');
    }

    // Direct relationship to preview (through feedback and category)
    public function preview()
    {
        return $this->hasOneThrough(
            newPreview::class,
            newCategory::class,
            'id',           // Foreign key on categories table
            'id',           // Foreign key on previews table
            'feedback_id',  // Local key on feedback_sets table (goes to feedback)
            'preview_id'    // Local key on categories table
        )->join('new_feedback', 'new_categories.id', '=', 'new_feedback.category_id')
            ->where('new_feedback.id', '=', $this->feedback_id ?? 0);
    }

    // Accessor to get preview name
    public function getPreviewNameAttribute()
    {
        return $this->feedback?->category?->preview?->name;
    }

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Feedback Set'; // name for this log

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
           ->useLogName('Feedback Set of: '. $this->preview_name);
    }
}
