<?php

// app/Models/FileTransfer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FileTransfer extends Model
{
    public $timestamps = true;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'File Transfer'; // name for this log

    protected $fillable = ['slug', 'name', 'client', 'user_id', 'file_path']; // Ensure 'user_id' is fillable

    // Define the relationship
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // 'user_id' should be an integer
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('File Transfer');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
}
