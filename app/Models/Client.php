<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ColorPalette;
use App\Models\User;

class Client extends Model
{
    protected $fillable = [
        'name',
        'website',
        'preview_url',
        'logo',
        'color_palette_id',
    ];

    /**
     * Scope to select only valid columns
     */
    public function scopeValidColumns($query)
    {
        return $query->select(['id', 'name', 'website', 'preview_url', 'logo', 'color_palette_id', 'created_at', 'updated_at']);
    }

    public function colorPalette()
    {
        return $this->belongsTo(ColorPalette::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
