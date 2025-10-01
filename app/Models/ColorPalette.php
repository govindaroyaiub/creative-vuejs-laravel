<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client;

class ColorPalette extends Model
{
    protected $fillable = [
        'name',
        'primary',
        'secondary',
        'tertiary',
        'quaternary',
        'quinary',
        'senary',
        'septenary',
        'feedbackTab_inactive_image',
        'feedbackTab_active_image',
        'rightSideTab_feedback_description_image',
        'rightSideTab_color_palette_image',
        'header_image',
        'status',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
