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
        Schema::create('new_socials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('new_versions')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            // Filled from the file by newSocial's saving hook.
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            // Frame in the client viewer: feed | story | link | pin | storyboard | plain. NULL = auto from size.
            $table->string('placement', 20)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_socials');
    }
};
