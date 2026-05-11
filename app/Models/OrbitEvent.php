<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrbitEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'orbit_embed_id',
        'type',
        'referrer',
        'ip',
        'user_agent',
    ];

    public function embed(): BelongsTo
    {
        return $this->belongsTo(OrbitEmbed::class, 'orbit_embed_id');
    }
}
