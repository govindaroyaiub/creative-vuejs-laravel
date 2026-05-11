<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orbit_embeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')
                ->constrained('new_banners')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique('banner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orbit_embeds');
    }
};
