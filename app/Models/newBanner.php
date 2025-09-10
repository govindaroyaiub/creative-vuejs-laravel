<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newVersion;
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Banner');
    }

    public function version()
    {
        return $this->belongsTo(newVersion::class, 'version_id');
    }


    public function size()
    {
        return $this->belongsTo(BannerSize::class, 'size_id');
    }
}
