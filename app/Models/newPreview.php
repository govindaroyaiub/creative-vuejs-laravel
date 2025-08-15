<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use App\Models\User;
use App\Models\ColorPalette;
use App\Models\newCategory;

class newPreview extends Model
{
    use HasFactory;

    protected $casts = [
        'team_members' => 'array',
    ];

    protected $fillable = [
        'slug',
        'name',
        'client_id',
        'team_members',
        'uploader_id',
        'color_palette_id',
        'requires_login',
        'show_planetnine_logo',
        'show_sidebar_logo',
        'show_footer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function colorPalette()
    {
        return $this->belongsTo(ColorPalette::class);
    }

    public function categories()
    {
        return $this->hasMany(newCategory::class, 'preview_id');
    }
}
