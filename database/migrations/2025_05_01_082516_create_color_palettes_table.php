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
        Schema::create('color_palettes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('primary');
            $table->string('secondary');
            $table->string('tertiary');
            $table->string('quaternary');
            $table->string('quinary');
            $table->string('senary');
            $table->string('septenary');
            $table->string('feedbackTab_inactive_image');
            $table->string('feedbackTab_active_image');
            $table->string('rightSideTab_feedback_description_image');
            $table->string('rightSideTab_color_palette_image');
            $table->string('header_image');
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('color_palettes');
    }
};
