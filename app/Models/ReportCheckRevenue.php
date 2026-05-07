<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCheckRevenue extends Model
{
    public const SECTION_DISPLAY = 'display';
    public const SECTION_STICKY = 'sticky';
    public const SECTION_INARTICLE = 'inarticle';
    public const SECTION_INTERSCROLLER = 'interscroller';

    protected $fillable = [
        'check_id',
        'date',
        'partner',
        'section',
        'revenue_eur',
        'revenue_local',
        'currency_local',
    ];

    protected $casts = [
        'date' => 'date',
        'revenue_eur' => 'decimal:4',
        'revenue_local' => 'decimal:4',
    ];

    public function check(): BelongsTo
    {
        return $this->belongsTo(ReportCheck::class, 'check_id');
    }
}
