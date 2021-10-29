<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Support\Helpers;

class CreateBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bots', function (Blueprint $table) {
            if (Messenger::shouldUseUuids()) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }
            $table->uuid('thread_id');
            Helpers::schemaMorphType('owner', $table);
            $table->string('name', 255);
            $table->string('avatar')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('hide_actions')->default(false);
            $table->integer('cooldown')->default(0);
            $table->timestamps();
            $table->softDeletes();
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
        Schema::dropIfExists('bots');
    }
}
