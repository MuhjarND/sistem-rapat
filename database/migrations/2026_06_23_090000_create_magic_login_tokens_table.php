<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMagicLoginTokensTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('magic_login_tokens')) {
            Schema::create('magic_login_tokens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('token_hash', 64)->unique();
                $table->string('redirect_path', 500)->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'expires_at']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('magic_login_tokens');
    }
}
