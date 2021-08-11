<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('type')->default(1);
            $table->string('subject', 255)->nullable();
            $table->string('image')->nullable();
            $table->boolean('add_participants')->default(false);
            $table->boolean('invitations')->default(false);
            $table->boolean('calling')->default(true);
            $table->boolean('messaging')->default(true);
            $table->boolean('knocks')->default(true);
            $table->boolean('chat_bots')->default(false);
            $table->boolean('lockout')->default(false);
            $table->timestamps(6);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('threads');
    }
}
