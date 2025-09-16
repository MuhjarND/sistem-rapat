<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterRoleEnumAddApprovalInUsersTable extends Migration
{
    public function up()
    {
        // Kalau pakai MySQL/MariaDB: ubah ENUM via raw SQL
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Pastikan tidak ada nilai role yang kosong (edge case lama)
            DB::statement("UPDATE `users` SET `role` = 'peserta' WHERE `role` = '' OR `role` IS NULL");

            // Tambah 'approval' ke daftar ENUM
            DB::statement("
                ALTER TABLE `users`
                MODIFY `role` ENUM('admin','notulis','peserta','approval')
                NOT NULL DEFAULT 'peserta'
            ");
        } else {
            // Fallback untuk driver lain: jadikan string biasa (opsional)
            Schema::table('users', function ($table) {
                $table->string('role', 20)->default('peserta')->change();
            });
        }
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Kembalikan ke enum awal tanpa 'approval'
            // Catatan: baris yang terlanjur 'approval' akan gagal jika tidak dibersihkan.
            DB::statement("UPDATE `users` SET `role` = 'peserta' WHERE `role` = 'approval'");

            DB::statement("
                ALTER TABLE `users`
                MODIFY `role` ENUM('admin','notulis','peserta')
                NOT NULL DEFAULT 'peserta'
            ");
        } else {
            Schema::table('users', function ($table) {
                $table->string('role', 20)->default('peserta')->change();
            });
        }
    }
}
