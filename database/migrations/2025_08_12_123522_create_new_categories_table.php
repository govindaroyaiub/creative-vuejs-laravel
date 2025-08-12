<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('new_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preview_id')->constrained('new_previews')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['banner', 'video', 'social', 'gif']);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_categories');
    }
};
