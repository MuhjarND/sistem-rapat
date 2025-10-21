<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrateUsersUnitEnumToString extends Migration
{
    public function up()
    {
        // 1) Tambah kolom sementara: unit_text (string)
        if (!Schema::hasColumn('users', 'unit_text')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('unit_text', 100)->nullable()->after('jabatan');
            });
        }

        // 2) Copy semua nilai dari ENUM lama -> unit_text
        if (Schema::hasColumn('users', 'unit')) {
            DB::table('users')->update([
                'unit_text' => DB::raw('unit')
            ]);
        }

        // 3) Drop kolom ENUM lama
        if (Schema::hasColumn('users', 'unit')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('unit');
            });
        }

        // 4) Rename unit_text -> unit (string)
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('unit_text', 'unit');
        });

        // 5) Set default & normalisasi nilai null (opsional)
        DB::table('users')->whereNull('unit')->update(['unit' => 'kesekretariatan']);
    }

    public function down()
    {
        // Rollback aman: buat kolom enum lama, copy back nilai, drop string.
        // PERHATIAN: daftar enum di bawah hardcode 2 opsi awal.
        Schema::table('users', function (Blueprint $table) {
            $table->enum('unit_tmp', ['kepaniteraan', 'kesekretariatan'])
                  ->default('kesekretariatan')
                  ->after('jabatan');
        });

        // Coerce string unit yang tidak termasuk ke default 'kesekretariatan'
        DB::table('users')->whereNotIn('unit', ['kepaniteraan', 'kesekretariatan'])
            ->update(['unit' => 'kesekretariatan']);

        DB::table('users')->update([
            'unit_tmp' => DB::raw('unit')
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('unit_tmp', 'unit');
        });
    }
}
