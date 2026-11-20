<?php

// database/migrations/YYYY_MM_DD_create_file_transfers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileTransfersTable extends Migration
{
    public function up()
    {
        Schema::create('file_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('client');
            $table->integer('preview_id')->nullable();
            $table->integer('user_id');
            $table->text('file_path');
            $table->timestamps();

            $table->index('created_at', 'file_transfers_created_at_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_transfers');
    }
}
