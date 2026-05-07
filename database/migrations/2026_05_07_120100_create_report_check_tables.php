<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_checks', function (Blueprint $table) {
            $table->id();
            $table->string('publisher')->default('f1maximaal');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('outcome_filename');
            $table->string('status')->default('pending'); // pending, pass, fail_minor, fail_major, error
            $table->decimal('fx_rate_used', 8, 4)->nullable();
            $table->json('outcome_snapshot')->nullable();
            $table->json('analytics_snapshot')->nullable();
            $table->json('totals_snapshot')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['publisher', 'period_start']);
            $table->index('status');
        });

        Schema::create('report_check_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_id')->constrained('report_checks')->cascadeOnDelete();
            $table->string('source_key'); // adform, gam, ogury, seedtag, showheroes, teads, adhese, outbrain, analytics, outcome
            $table->string('filename');
            $table->string('sha256', 64);
            $table->unsignedInteger('parsed_row_count')->default(0);
            $table->timestamps();

            $table->unique(['check_id', 'source_key']);
        });

        Schema::create('report_check_revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_id')->constrained('report_checks')->cascadeOnDelete();
            $table->date('date');
            $table->string('partner');                           // Adform, GAM, Ogury, Seedtag, Showheroes, Teads, Adhese, Outbrain
            $table->string('section');                           // display, sticky, inarticle, interscroller
            $table->decimal('revenue_eur', 14, 4);               // recomputed truth, after FX
            $table->decimal('revenue_local', 14, 4)->nullable(); // raw before FX (only for Ogury USD)
            $table->string('currency_local', 3)->nullable();     // 'USD' or null
            $table->timestamps();

            $table->unique(['check_id', 'date', 'partner', 'section']);
            $table->index(['check_id', 'date']);
        });

        Schema::create('report_check_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_id')->constrained('report_checks')->cascadeOnDelete();
            $table->string('sheet');                       // Summary, Trend, Demand Partners, Sticky, Inarticle, Display, Interscroller
            $table->string('cell_ref')->nullable();        // e.g., D6 (null for issues not tied to one cell)
            $table->string('kind');                        // value, row_total, col_total, grand_total, derived, rate_outlier, missing_source, parse_error
            $table->string('severity');                    // minor, major
            $table->decimal('expected', 14, 4)->nullable();
            $table->decimal('found', 14, 4)->nullable();
            $table->decimal('delta', 14, 4)->nullable();
            $table->text('message');
            $table->timestamps();

            $table->index(['check_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_check_issues');
        Schema::dropIfExists('report_check_revenues');
        Schema::dropIfExists('report_check_files');
        Schema::dropIfExists('report_checks');
    }
};
