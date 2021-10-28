<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RTippin\Messenger\Support\Helpers;

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
            Helpers::schemaMorphType('owner', $table);
            $table->integer('type')->default(1);
            $table->boolean('setup_complete')->default(false);
            $table->boolean('teardown_complete')->default(false);
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
