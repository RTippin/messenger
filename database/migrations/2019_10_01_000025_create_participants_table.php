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
            Helpers::SchemaMorphType('owner', $table);
            $table->boolean('admin')->default(0);
            $table->boolean('muted')->default(0);
            $table->boolean('pending')->default(0);
            $table->boolean('start_calls')->default(0);
            $table->boolean('send_knocks')->default(0);
            $table->boolean('send_messages')->default(1);
            $table->boolean('add_participants')->default(0);
            $table->boolean('manage_invites')->default(0);
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
