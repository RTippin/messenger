<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RTippin\Messenger\Support\Helpers;

class CreateParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('thread_id');
            Helpers::schemaMorphType('owner', $table);
            $table->boolean('admin')->default(false);
            $table->boolean('muted')->default(false);
            $table->boolean('pending')->default(false);
            $table->boolean('start_calls')->default(false);
            $table->boolean('send_knocks')->default(false);
            $table->boolean('send_messages')->default(true);
            $table->boolean('add_participants')->default(false);
            $table->boolean('manage_invites')->default(false);
            $table->boolean('manage_bots')->default(false);
            $table->timestamp('last_read', 6)->nullable()->default(null);
            $table->timestamps(6);
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
        Schema::dropIfExists('participants');
    }
}
