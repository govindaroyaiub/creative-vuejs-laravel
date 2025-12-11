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
        Schema::create('new_previews', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->integer('header_logo_id');
            $table->json('team_members');
            $table->foreignId('uploader_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('requires_login')->default(false);
            $table->boolean('show_planetnine_logo')->default(true);
            $table->boolean('show_sidebar_logo')->default(true);
            $table->boolean('show_footer')->default(true);
            $table->foreignId('color_palette_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('filetransfer_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_previews');
    }
};
