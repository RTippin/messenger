<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('thread_id');
            $table->uuidMorphs('owner');
            $table->integer('type')->default(1);
            $table->boolean('setup_complete')->default(0);
            $table->string('room_id')->nullable();
            $table->string('room_pin')->nullable();
            $table->string('room_secret')->nullable();
            $table->string('payload', 2000)->nullable();
            $table->timestamp('call_ended')->nullable()->index();
            $table->timestamps();
            $table->foreign('thread_id')
                ->references('id')
                ->on('threads')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calls');
    }
}
