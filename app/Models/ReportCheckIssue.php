<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCheckIssue extends Model
{
    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MAJOR = 'major';

    public const KIND_VALUE = 'value';
    public const KIND_ROW_TOTAL = 'row_total';
    public const KIND_COL_TOTAL = 'col_total';
    public const KIND_GRAND_TOTAL = 'grand_total';
    public const KIND_DERIVED = 'derived';
    public const KIND_RATE_OUTLIER = 'rate_outlier';
    public const KIND_MISSING_SOURCE = 'missing_source';
    public const KIND_PARSE_ERROR = 'parse_error';

    protected $fillable = [
        'check_id',
        'sheet',
        'cell_ref',
        'kind',
        'severity',
        'expected',
        'found',
        'delta',
        'message',
        'explanation',
    ];

    protected $casts = [
        'expected' => 'decimal:4',
        'found' => 'decimal:4',
        'delta' => 'decimal:4',
        'explanation' => 'array',
    ];

    public function check(): BelongsTo
    {
        return $this->belongsTo(ReportCheck::class, 'check_id');
    }
}
