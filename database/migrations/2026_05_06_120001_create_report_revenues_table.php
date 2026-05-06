<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('report_sources')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('revenue', 12, 4);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();

            $table->unique(['source_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_revenues');
    }
};
