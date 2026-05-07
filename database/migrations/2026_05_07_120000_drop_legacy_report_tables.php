<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('report_revenues');
        Schema::dropIfExists('report_sources');
    }

    public function down(): void
    {
    }
};
