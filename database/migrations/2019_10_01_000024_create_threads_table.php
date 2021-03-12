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
            $table->string('subject')->nullable();
            $table->string('image')->nullable();
            $table->boolean('add_participants')->default(0);
            $table->boolean('invitations')->default(0);
            $table->boolean('calling')->default(1);
            $table->boolean('messaging')->default(1);
            $table->boolean('knocks')->default(1);
            $table->boolean('lockout')->default(0);
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
