<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RTippin\Messenger\Support\Helpers;

class CreateMessengersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messengers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            Helpers::schemaMorphType('owner', $table);
            $table->boolean('message_popups')->default(true);
            $table->boolean('message_sound')->default(true);
            $table->boolean('call_ringtone_sound')->default(true);
            $table->boolean('notify_sound')->default(true);
            $table->boolean('dark_mode')->default(true);
            $table->integer('online_status')->default(1);
            $table->string('ip')->nullable()->default(null);
            $table->string('timezone')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messengers');
    }
}
