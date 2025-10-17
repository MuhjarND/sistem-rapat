<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddOperatorToRoleEnumOnUsersTable extends Migration
{
    public function up(): void
    {
        // Sesuaikan daftar enum di bawah dengan daftar role yang ada di DB kamu saat ini
        DB::statement("
            ALTER TABLE users 
            MODIFY role ENUM('admin','operator','notulis','peserta','approval')
            NOT NULL DEFAULT 'peserta'
        ");
    }

    public function down(): void
    {
        // Kembalikan ke daftar sebelumnya (tanpa 'operator') — sesuaikan jika berbeda
        DB::statement("
            ALTER TABLE users 
            MODIFY role ENUM('admin','notulis','peserta','approval')
            NOT NULL DEFAULT 'peserta'
        ");
    }
};
