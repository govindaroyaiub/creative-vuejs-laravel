<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\newFeedback;
use App\Models\newVersion;

class newFeedbackSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'feedback_id',
        'name'
    ];

    public function feedback()
    {
        return $this->belongsTo(newFeedback::class, 'feedback_id');
    }

    public function versions()
    {
        return $this->hasMany(newVersion::class, 'feedback_set_id');
    }
}
