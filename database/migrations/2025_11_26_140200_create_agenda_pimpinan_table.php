<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgendaPimpinanTable extends Migration
{
    public function up()
    {
        Schema::create('agenda_pimpinan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pimpinan_user_id');
            $table->date('tanggal');
            $table->time('waktu');
            $table->string('judul');
            $table->string('tempat');
            $table->text('yang_menghadiri')->nullable();
            $table->string('seragam')->nullable();
            $table->string('lampiran_path')->nullable();
            $table->string('lampiran_nama')->nullable();
            $table->unsignedBigInteger('lampiran_size')->nullable();
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamps();

            $table->index(['pimpinan_user_id', 'tanggal']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('agenda_pimpinan');
    }
}
