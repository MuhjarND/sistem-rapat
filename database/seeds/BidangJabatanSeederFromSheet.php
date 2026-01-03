<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BidangJabatanSeederFromSheet extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('jabatan') && !Schema::hasTable('bidang')) {
            $this->command && $this->command->warn('Tabel jabatan/bidang belum ada.');
            return;
        }

        $path = database_path('seeds/User/Rekap Data Pegawai.csv');
        if (!file_exists($path)) {
            $this->command && $this->command->warn('CSV tidak ditemukan: '.$path);
            return;
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->command && $this->command->warn('Gagal membuka CSV: '.$path);
            return;
        }

        $headers = fgetcsv($handle, 0, ';');
        if (!$headers) {
            fclose($handle);
            $this->command && $this->command->warn('Header CSV kosong.');
            return;
        }

        $headers = array_map(function ($h) {
            $h = trim((string) $h);
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            return strtolower($h);
        }, $headers);
        $index = array_flip($headers);

        $jabatanSet = [];
        $bidangSet = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) === 1 && $row[0] === null) continue;

            $jabRaw = $index['jabatan'] ?? null;
            if ($jabRaw !== null) {
                $jab = isset($row[$jabRaw]) ? trim($row[$jabRaw]) : '';
                if ($jab !== '') {
                    $jab = preg_replace('/\s+/', ' ', $jab);
                    $jabatanSet[$jab] = true;
                }
            }

            $bidRaw = $index['bidang'] ?? null;
            if ($bidRaw !== null) {
                $bid = isset($row[$bidRaw]) ? trim($row[$bidRaw]) : '';
                if ($bid !== '') {
                    $bid = preg_replace('/\s+/', ' ', $bid);
                    $bidangSet[$bid] = true;
                }
            }
        }
        fclose($handle);

        $now = now();

        if (Schema::hasTable('jabatan')) {
            foreach (array_keys($jabatanSet) as $nama) {
                $existing = DB::table('jabatan')->where('nama', $nama)->first();
                if ($existing) {
                    DB::table('jabatan')->where('id', $existing->id)->update([
                        'is_active' => 1,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('jabatan')->insert([
                        'nama' => $nama,
                        'kategori' => null,
                        'keterangan' => null,
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if (Schema::hasTable('bidang')) {
            foreach (array_keys($bidangSet) as $nama) {
                $existing = DB::table('bidang')->where('nama', $nama)->first();
                if ($existing) {
                    DB::table('bidang')->where('id', $existing->id)->update([
                        'is_active' => 1,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('bidang')->insert([
                        'nama' => $nama,
                        'singkatan' => null,
                        'keterangan' => null,
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
