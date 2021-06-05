<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RTippin\Messenger\Support\Helpers;

class CreateBotActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bot_id');
            Helpers::SchemaMorphType('owner', $table);
            $table->string('handler');
            $table->string('trigger')->nullable();
            $table->boolean('admin_only')->default(false);
            $table->string('match_method')->default('exact');
            $table->text('payload')->nullable()->default(null);
            $table->timestamps();
            $table->foreign('bot_id')
                ->references('id')
                ->on('bots')
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
        Schema::dropIfExists('bot_actions');
    }
}
