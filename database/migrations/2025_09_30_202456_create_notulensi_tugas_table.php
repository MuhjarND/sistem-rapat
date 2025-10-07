<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotulensiTugasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notulensi_tugas', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('id_notulensi_detail');
            $t->unsignedBigInteger('user_id');
            $t->date('tgl_penyelesaian')->nullable(); // mirror due-date per assignment (optional)
            $t->enum('status', ['pending','done'])->default('pending');
            $t->timestamps();

            $t->foreign('id_notulensi_detail')->references('id')->on('notulensi_detail')->onDelete('cascade');
            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $t->index(['user_id','status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notulensi_tugas');
    }
}
