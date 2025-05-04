<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Version;

class SubVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_id',
        'name',
        'path',
        'is_active',
    ];

    public function version() {
        return $this->belongsTo(Version::class);
    }
}
