<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrbitEmbed extends Model
{
    protected $fillable = ['banner_id', 'is_active'];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function banner(): BelongsTo
    {
        return $this->belongsTo(newBanner::class, 'banner_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrbitEvent::class);
    }
}
