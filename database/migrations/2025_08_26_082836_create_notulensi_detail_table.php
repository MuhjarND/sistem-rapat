<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotulensiDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notulensi_detail', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_notulensi');
            $table->integer('urut')->default(1);
            $table->text('hasil_pembahasan');           // kolom kiri
            $table->text('rekomendasi')->nullable();    // kolom tengah
            $table->string('penanggung_jawab')->nullable(); // kolom PJ (bebas teks; bisa diupgrade ke relasi users)
            $table->date('tgl_penyelesaian')->nullable();   // kolom tanggal penyelesaian
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
        Schema::dropIfExists('notulensi_detail');
    }
}
