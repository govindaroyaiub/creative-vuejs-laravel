<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newCategory;
use App\Models\newFeedbackSet;

class newFeedback extends Model
{
    use HasFactory;

    protected $table = 'new_feedback';

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(newCategory::class, 'category_id');
    }

    public function feedbackSets()
    {
        return $this->hasMany(newFeedbackSet::class, 'feedback_id');
    }
}
