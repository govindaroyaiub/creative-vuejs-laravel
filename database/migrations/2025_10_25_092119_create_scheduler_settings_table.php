<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduler_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default cache cleanup schedule
        DB::table('scheduler_settings')->insert([
            [
                'key' => 'cache_cleanup_enabled',
                'value' => 'true',
                'description' => 'Enable/disable automatic cache cleanup',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'cache_cleanup_time',
                'value' => '04:30',
                'description' => 'Time for daily cache cleanup (HH:MM format)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduler_settings');
    }
};
