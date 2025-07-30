<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Preview;
use App\Models\SubVersion;

class Version extends Model
{
    use HasFactory;

    protected $fillable = [
        'preview_id',
        'name',
        'type',
        'is_active',
    ];

    public function preview()
    {
        return $this->belongsTo(Preview::class);
    }

    public function subVersions()
    {
        return $this->hasMany(SubVersion::class);
    }
}
