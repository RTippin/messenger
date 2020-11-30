<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMobileDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('owner');
            $table->string('device_id')->unique();
            $table->string('oauth_access_token', 100)->nullable()->index();
            $table->string('device_token')->index()->nullable();
            $table->string('device_os');
            $table->string('device_name')->nullable();
            $table->string('voip_token')->nullable();
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
        Schema::dropIfExists('mobile_devices');
    }
}
