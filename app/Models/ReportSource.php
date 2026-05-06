<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportSource extends Model
{
    protected $fillable = [
        'key',
        'display_name',
        'section',
        'filename_pattern',
        'sheet_name',
        'header_row',
        'date_column',
        'date_format',
        'revenue_column',
        'column_order',
        'active',
    ];

    protected $casts = [
        'header_row' => 'integer',
        'column_order' => 'integer',
        'active' => 'boolean',
    ];

    public function revenues(): HasMany
    {
        return $this->hasMany(ReportRevenue::class, 'source_id');
    }
}
