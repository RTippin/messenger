<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RTippin\Messenger\Support\Helpers;

class CreateCallParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('call_id');
            Helpers::schemaMorphType('owner', $table);
            $table->boolean('kicked')->default(false);
            $table->timestamp('left_call')->nullable();
            $table->timestamps();
            $table->foreign('call_id')
                ->references('id')
                ->on('calls')
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
        Schema::dropIfExists('call_participants');
    }
}
