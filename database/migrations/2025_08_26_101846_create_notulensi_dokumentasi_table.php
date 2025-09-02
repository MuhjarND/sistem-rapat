<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotulensiDokumentasiTable extends Migration
{
    public function up()
    {
        Schema::create('notulensi_dokumentasi', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_notulensi');     // relasi ke notulensi
            $table->string('file_path');                    // contoh: notulensi/abc.jpg
            $table->string('caption')->nullable();          // opsional
            $table->timestamps();

            // optional: FK + cascade
            // $table->foreign('id_notulensi')->references('id')->on('notulensi')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notulensi_dokumentasi');
    }
}
