<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportDay extends Model
{
    protected $fillable = [
        'site',
        'date',
        'revenue',
        'impressions',
        'total_ad_requests',
        'analytics',
        'impressions_sold',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'revenue' => 'array',
            'impressions' => 'array',
            'analytics' => 'array',
            'total_ad_requests' => 'integer',
            'impressions_sold' => 'integer',
        ];
    }
}
