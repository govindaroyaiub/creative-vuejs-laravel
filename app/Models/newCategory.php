<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newPreview;
use App\Models\newFeedback;

class newCategory extends Model
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
        return $this->belongsTo(newPreview::class, 'preview_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(newFeedback::class, 'category_id');
    }
}
