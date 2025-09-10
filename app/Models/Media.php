<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Media extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Media'; // name for this log

    protected $fillable = ['name', 'path', 'uploader_id'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Media');
    }
}
