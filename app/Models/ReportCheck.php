<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCheck extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PASS = 'pass';
    public const STATUS_FAIL_MINOR = 'fail_minor';
    public const STATUS_FAIL_MAJOR = 'fail_major';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'publisher',
        'period_start',
        'period_end',
        'outcome_filename',
        'status',
        'fx_rate_used',
        'outcome_snapshot',
        'analytics_snapshot',
        'totals_snapshot',
        'error_message',
        'uploaded_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'fx_rate_used' => 'decimal:4',
        'outcome_snapshot' => 'array',
        'analytics_snapshot' => 'array',
        'totals_snapshot' => 'array',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(ReportCheckFile::class, 'check_id');
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(ReportCheckRevenue::class, 'check_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(ReportCheckIssue::class, 'check_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
