<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Version;

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

    public function subVersion()
    {
        return $this->belongsTo(Version::class);
    }
}
