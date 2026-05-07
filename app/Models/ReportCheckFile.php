<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCheckFile extends Model
{
    protected $fillable = [
        'check_id',
        'source_key',
        'filename',
        'sha256',
        'parsed_row_count',
    ];

    public function check(): BelongsTo
    {
        return $this->belongsTo(ReportCheck::class, 'check_id');
    }
}
