<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaporanFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laporan_files', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('id_rapat')->nullable();   // opsional: kaitkan ke rapat tertentu
            $t->string('judul');
            $t->date('tanggal_laporan')->nullable();
            $t->text('keterangan')->nullable();

            $t->string('file_name');   // nama asli
            $t->string('file_path');   // path relatif: "laporan/..."
            $t->string('mime')->nullable();
            $t->unsignedBigInteger('size')->default(0);

            $t->unsignedBigInteger('uploaded_by'); // id user
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('laporan_files');
    }
}
