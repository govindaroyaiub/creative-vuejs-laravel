<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newFeedback;
use App\Models\newVersion;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newFeedbackSet extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Feedback Set'; // name for this log

    protected $fillable = [
        'feedback_id',
        'name'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Feedback Set');
    }

    public function feedback()
    {
        return $this->belongsTo(newFeedback::class, 'feedback_id');
    }

    public function versions()
    {
        return $this->hasMany(newVersion::class, 'feedback_set_id');
    }
}
