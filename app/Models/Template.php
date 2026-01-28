<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Template extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Template'; // name for this log

    protected $fillable = [
        'name',
        'file_path',
        'file_name',
        'url',
    ];

    /**
     * Determine if this template is an external link.
     *
     * @return bool
     */
    public function isLink(): bool
    {
        return !empty($this->url) && empty($this->file_path);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Template of: ' . $this->name);
    }
}
