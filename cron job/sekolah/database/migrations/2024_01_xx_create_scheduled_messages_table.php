<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->string('recipient');
            $table->text('message');
            $table->timestamp('scheduled_time');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('scheduled_messages');
    }
}