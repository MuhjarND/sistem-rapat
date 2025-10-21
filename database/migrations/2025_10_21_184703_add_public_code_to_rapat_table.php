<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicCodeToRapatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
        public function up()
        {
            Schema::table('rapat', function (Blueprint $table) {
                $table->string('public_code', 50)->nullable()->unique()->after('id');
            });
        }

        public function down()
        {
            Schema::table('rapat', function (Blueprint $table) {
                $table->dropColumn('public_code');
            });
        }

}
