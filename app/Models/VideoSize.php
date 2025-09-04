<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newVideo;

class VideoSize extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'width', 'height'];

    public function videos()
    {
        return $this->hasMany(newVideo::class, 'size_id');
    }
}
