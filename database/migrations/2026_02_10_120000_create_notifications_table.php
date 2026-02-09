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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // category_created, feedback_created, feedback_set_created, version_created, asset_created
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable(); // Store related IDs and additional info
            $table->string('link')->nullable(); // URL to the related resource
            $table->foreignId('preview_id')->nullable()->constrained('new_previews')->onDelete('cascade');
            $table->foreignId('actor_id')->nullable()->constrained('users')->onDelete('set null'); // Who created the item
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index('preview_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
