<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use App\Models\User;
use App\Models\ColorPalette;
use App\Models\newCategory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newPreview extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Preview'; // name for this log

    protected $casts = [
        'team_members' => 'array',
    ];

    protected $fillable = [
        'slug',
        'name',
        'client_id',
        'header_logo_id',
        'team_members',
        'uploader_id',
        'color_palette_id',
        'requires_login',
        'show_planetnine_logo',
        'show_sidebar_logo',
        'show_footer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Preview');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function colorPalette()
    {
        return $this->belongsTo(ColorPalette::class);
    }

    public function categories()
    {
        return $this->hasMany(newCategory::class, 'preview_id');
    }
}
