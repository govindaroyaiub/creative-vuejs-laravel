<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Version;

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
        return $this->belongsTo(Version::class);
    }
}
