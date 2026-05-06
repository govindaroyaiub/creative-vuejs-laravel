<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_sources', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('display_name');
            $table->string('section');
            $table->string('filename_pattern');
            $table->string('sheet_name');
            $table->unsignedSmallInteger('header_row')->default(1);
            $table->string('date_column');
            $table->string('date_format')->nullable();
            $table->string('revenue_column');
            $table->unsignedSmallInteger('column_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['section', 'column_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_sources');
    }
};
