<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\SubVersion;
use App\Models\Social;

class SubSocial extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_version_id',
        'name',
        'path',
        'social_id',
        'position',
    ];

    public function subVersion()
    {
        return $this->belongsTo(SubVersion::class);
    }

    public function social()
    {
        return $this->belongsTo(Social::class);
    }
}