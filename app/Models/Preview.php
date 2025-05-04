<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use App\Models\User;
use App\Models\ColorPalette;
use App\Models\Version;

class Preview extends Model
{
    use HasFactory;

    protected $casts = [
        'team_members' => 'array',
    ];

    protected $fillable = [
        'name',
        'client_id',
        'team_members',
        'uploader_id',
        'color_palette_id',
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

    public function versions()
    {
        return $this->hasMany(Version::class);
    }
}
