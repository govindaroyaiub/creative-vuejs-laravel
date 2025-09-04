<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\newVersion;
use App\Models\BannerSize;

class newGif extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_id',
        'name',
        'path',
        'size_id',
        'file_size',
        'position',
    ];

    public function version()
    {
        return $this->belongsTo(newVersion::class, 'version_id');
    }

    public function size()
    {
        return $this->belongsTo(BannerSize::class, 'size_id');
    }
}
