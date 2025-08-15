<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Version;

class newSocial extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_id',
        'name',
        'path',
        'position',
    ];

    public function version()
    {
        return $this->belongsTo(Version::class);
    }
}
