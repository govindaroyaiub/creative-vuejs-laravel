<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\SubVersion;

class SubVideo extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'sub_version_id',
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
        return $this->belongsTo(SubVersion::class);
    }
}
