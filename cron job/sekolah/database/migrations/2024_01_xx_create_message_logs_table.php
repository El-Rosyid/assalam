<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageLogsTable extends Migration
{
    public function up()
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient');
            $table->text('message_content');
            $table->string('status'); // e.g., sent, failed
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_logs');
    }
}