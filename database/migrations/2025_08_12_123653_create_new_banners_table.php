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
        Schema::create('new_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('new_versions')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->foreignId('size_id')->constrained('banner_sizes')->cascadeOnDelete();
            $table->string('file_size');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_banners');
    }
};
