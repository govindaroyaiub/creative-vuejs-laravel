<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\newVersion;
use App\Models\VideoSize;

class newVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_id',
        'name',
        'path',
        'size_id',
        'codec',
        'aspect_ratio',
        'fps',
        'file_size',
        'companion_banner_path',
        'position',
    ];

    public function version()
    {
        return $this->belongsTo(newVersion::class, 'version_id');
    }

    public function size()
    {
        return $this->belongsTo(VideoSize::class, 'size_id');
    }
}
