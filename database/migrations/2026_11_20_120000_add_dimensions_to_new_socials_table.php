<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Social images had no stored size, so the preview could not reserve their
 * box before the image arrived and the page jumped as each one loaded.
 * Banners, GIFs and videos already get theirs from banner_sizes / video_sizes.
 *
 * New rows are filled by newSocial's saving hook. Existing rows are read off
 * disk here; any file that is missing or unreadable keeps NULL, and the card
 * falls back to a square placeholder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_socials', function (Blueprint $table) {
            $table->unsignedInteger('width')->nullable()->after('path');
            $table->unsignedInteger('height')->nullable()->after('width');
        });

        DB::table('new_socials')
            ->select(['id', 'path'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $file = public_path($row->path);
                    $info = is_file($file) ? @getimagesize($file) : false;
                    if ($info) {
                        DB::table('new_socials')->where('id', $row->id)->update([
                            'width' => $info[0],
                            'height' => $info[1],
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('new_socials', function (Blueprint $table) {
            $table->dropColumn(['width', 'height']);
        });
    }
};
