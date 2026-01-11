<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVirtualFieldsToRapatTable extends Migration
{
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'is_virtual')) {
                $table->boolean('is_virtual')->default(false)->after('id_kategori');
            }
            if (!Schema::hasColumn('rapat', 'link_zoom')) {
                $table->string('link_zoom', 255)->nullable()->after('is_virtual');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat', 'link_zoom')) {
                $table->dropColumn('link_zoom');
            }
            if (Schema::hasColumn('rapat', 'is_virtual')) {
                $table->dropColumn('is_virtual');
            }
        });
    }
}
