<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBidangTable extends Migration
{
    public function up()
    {
        Schema::create('bidang', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 120)->unique();
            $table->string('singkatan', 40)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bidang');
    }
}

