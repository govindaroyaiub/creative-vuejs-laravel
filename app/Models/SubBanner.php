<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\SubVersion;

class SubBanner extends Model
{
    use HasFactory;

    protected $fillable = ['sub_version_id', 'title', 'name', 'path', 'size_id', 'file_size', 'position'];

    public function subVersion()
    {
        return $this->belongsTo(SubVersion::class);
    }
}
