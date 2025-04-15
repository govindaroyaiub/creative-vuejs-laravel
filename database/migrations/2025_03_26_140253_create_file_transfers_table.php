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
            $table->string('name');
            $table->string('client');
            $table->integer('user_id');
            $table->text('file_path');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_transfers');
    }
}
