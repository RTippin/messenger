<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RTippin\Messenger\Support\Helpers;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('thread_id');
            Helpers::schemaMorphType('owner', $table);
            $table->integer('type')->index();
            $table->text('body')->nullable();
            $table->uuid('reply_to_id')->nullable()->index();
            $table->boolean('edited')->default(false);
            $table->boolean('reacted')->default(false);
            $table->boolean('embeds')->default(true);
            $table->text('extra')->nullable()->default(null);
            $table->timestamps(6);
            $table->softDeletes();
            $table->index('created_at');
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
        Schema::dropIfExists('messages');
    }
}
