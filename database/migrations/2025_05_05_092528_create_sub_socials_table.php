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
        Schema::create('sub_socials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_version_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('path');

            // Make this nullable for ON DELETE SET NULL to work
            $table->foreignId('social_id')->nullable()->constrained('socials')->nullOnDelete();

            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_socials');
    }
};
