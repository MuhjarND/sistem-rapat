<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvotingTables extends Migration
{
    public function up()
    {
        Schema::create('evotings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('evoting_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('evoting_id');
            $table->string('judul', 200);
            $table->unsignedInteger('urut')->default(1);
            $table->timestamps();

            $table->index(['evoting_id', 'urut']);
        });

        Schema::create('evoting_candidates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_id');
            $table->string('nama', 200);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('urut')->default(1);
            $table->timestamps();

            $table->index(['item_id', 'urut']);
        });

        Schema::create('evoting_voters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('evoting_id');
            $table->unsignedBigInteger('user_id');
            $table->string('token', 64)->unique();
            $table->timestamp('voted_at')->nullable();
            $table->timestamps();

            $table->unique(['evoting_id', 'user_id']);
            $table->index(['evoting_id', 'voted_at']);
        });

        Schema::create('evoting_votes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('evoting_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('voter_id');
            $table->timestamps();

            $table->unique(['evoting_id', 'voter_id', 'item_id']);
            $table->index(['evoting_id', 'item_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('evoting_votes');
        Schema::dropIfExists('evoting_voters');
        Schema::dropIfExists('evoting_candidates');
        Schema::dropIfExists('evoting_items');
        Schema::dropIfExists('evotings');
    }
}
