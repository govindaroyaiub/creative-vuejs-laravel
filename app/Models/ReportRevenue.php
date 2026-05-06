<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRevenue extends Model
{
    protected $fillable = [
        'source_id',
        'date',
        'revenue',
        'source_week',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'date' => 'date',
        'revenue' => 'decimal:4',
        'source_week' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ReportSource::class, 'source_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
