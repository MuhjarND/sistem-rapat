<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitsTable extends Migration
{
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nama')->unique();
            $table->string('singkatan')->nullable()->unique();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // kalau tabel users ada kolom unit (string) & kamu mau normalisasi nanti, biarkan dulu.
    }

    public function down()
    {
        Schema::dropIfExists('units');
    }
}
