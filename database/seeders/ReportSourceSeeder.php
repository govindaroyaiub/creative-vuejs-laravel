<?php

namespace Database\Seeders;

use App\Models\ReportSource;
use Illuminate\Database\Seeder;

class ReportSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            // Outstream Ad Position
            [
                'key' => 'gam_outstream',
                'display_name' => 'AdX',
                'section' => 'Outstream',
                'filename_pattern' => 'GAM - Outstream',
                'sheet_name' => 'Report data',
                'header_row' => 1,
                'date_column' => 'Date',
                'date_format' => null,
                'revenue_column' => 'Ad Exchange revenue (€)',
                'column_order' => 1,
            ],
            [
                'key' => 'seedtag_outstream',
                'display_name' => 'SeedTag',
                'section' => 'Outstream',
                'filename_pattern' => 'SeedTag - Outstream',
                'sheet_name' => 'Revenue',
                'header_row' => 1,
                'date_column' => 'Date',
                'date_format' => null,
                'revenue_column' => 'Revenue',
                'column_order' => 2,
            ],
            [
                'key' => 'showheroes',
                'display_name' => 'ShowHeroes',
                'section' => 'Outstream',
                'filename_pattern' => 'ShowHeroes',
                'sheet_name' => 'Sheet1',
                'header_row' => 1,
                'date_column' => 'Date and Time',
                'date_format' => null,
                'revenue_column' => 'Publisher Revenue',
                'column_order' => 3,
            ],
            [
                'key' => 'teads',
                'display_name' => 'Teads',
                'section' => 'Outstream',
                'filename_pattern' => 'Teads',
                'sheet_name' => 'default',
                'header_row' => 10,
                'date_column' => 'Day',
                'date_format' => 'm/d/Y',
                'revenue_column' => 'Estimated Earnings in EUR',
                'column_order' => 4,
            ],
            [
                'key' => 'gam_prebid',
                'display_name' => 'GAM Prebid',
                'section' => 'Outstream',
                'filename_pattern' => 'GAM - Prebid',
                'sheet_name' => 'Report data',
                'header_row' => 1,
                'date_column' => 'Date',
                'date_format' => null,
                'revenue_column' => 'Ad Exchange revenue (€)',
                'column_order' => 5,
            ],

            // Sticky Bottom Ad Position
            [
                'key' => 'gam_sticky',
                'display_name' => 'AdX',
                'section' => 'Sticky',
                'filename_pattern' => 'GAM - Sticky',
                'sheet_name' => 'Report data',
                'header_row' => 1,
                'date_column' => 'Date',
                'date_format' => null,
                'revenue_column' => 'Ad Exchange revenue (€)',
                'column_order' => 1,
            ],
            [
                'key' => 'seedtag_sticky',
                'display_name' => 'SeedTag',
                'section' => 'Sticky',
                'filename_pattern' => 'SeedTag - Sticky',
                'sheet_name' => 'Revenue',
                'header_row' => 1,
                'date_column' => 'Date',
                'date_format' => null,
                'revenue_column' => 'Revenue',
                'column_order' => 2,
            ],
        ];

        foreach ($sources as $source) {
            ReportSource::updateOrCreate(
                ['key' => $source['key']],
                $source + ['active' => true],
            );
        }
    }
}
