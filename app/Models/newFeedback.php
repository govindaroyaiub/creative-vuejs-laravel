<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newCategory;
use App\Models\newFeedbackSet;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newFeedback extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Feedback'; // name for this log

    protected $table = 'new_feedback';

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Feedback');
    }

    public function category()
    {
        return $this->belongsTo(newCategory::class, 'category_id');
    }

    public function feedbackSets()
    {
        return $this->hasMany(newFeedbackSet::class, 'feedback_id');
    }
}
