<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newPreview;
use App\Models\newFeedback;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newCategory extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Category'; // name for this log

    protected $fillable = [
        'preview_id',
        'name',
        'type',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Category');
    }

    public function preview()
    {
        return $this->belongsTo(newPreview::class, 'preview_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(newFeedback::class, 'category_id');
    }
}
