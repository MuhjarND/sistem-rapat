<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserSeederFromSheet extends Seeder
{
    public function run()
    {
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
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // strip UTF-8 BOM
            return strtolower($h);
        }, $headers);
        $index = array_flip($headers);

        $get = function(array $row, string $key) use ($index): string {
            if (!isset($index[$key])) return '';
            $val = $row[$index[$key]] ?? '';
            return is_string($val) ? trim($val) : '';
        };

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) === 1 && $row[0] === null) continue;

            $email = strtolower($get($row, 'email'));
            if ($email === '') continue;

            $name       = $get($row, 'nama');
            $jabatan    = $get($row, 'jabatan');
            $jabKet     = $get($row, 'keterangan jabatan');
            $noHp       = $get($row, 'nomor hp');
            $unit       = $get($row, 'unit');
            $bidang     = $get($row, 'bidang');
            $tingkatanS = $get($row, 'tingkatan');
            $hirarkiS   = $get($row, 'hirarki');
            $roleRaw    = strtolower($get($row, 'role'));
            $passPlain  = $get($row, 'password');

            $tingkatan = null;
            if ($tingkatanS !== '') {
                if (preg_match('/\d+/', $tingkatanS, $m)) {
                    $tingkatan = (int) $m[0];
                }
            }

            $hirarki = is_numeric($hirarkiS) ? (int) $hirarkiS : null;

            $roleMap = [
                'admin' => 'admin',
                'operator' => 'operator',
                'notulis' => 'notulis',
                'peserta' => 'peserta',
                'approval' => 'approval',
                'protokoler' => 'protokoler',
            ];
            $role = $roleMap[$roleRaw] ?? 'peserta';
            if ($role === 'operator') {
                $tingkatan = null;
            } elseif (!empty($tingkatan)) {
                $role = 'approval';
            }

            if ($passPlain === '') {
                $passPlain = Str::random(12);
            }

            $data = [
                'name' => $name !== '' ? $name : $email,
                'email' => $email,
                'password' => Hash::make($passPlain),
                'role' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('users', 'jabatan')) {
                $data['jabatan'] = $jabatan !== '' ? $jabatan : null;
            }
            if (Schema::hasColumn('users', 'jabatan_keterangan')) {
                $data['jabatan_keterangan'] = $jabKet !== '' ? $jabKet : null;
            }
            if (Schema::hasColumn('users', 'jabatan_id') && $jabatan !== '') {
                $jabatanId = null;
                if (Schema::hasTable('jabatan')) {
                    $jabatanId = DB::table('jabatan')->where('nama', $jabatan)->value('id');
                    if (!$jabatanId) {
                        $jabatanId = DB::table('jabatan')->insertGetId([
                            'nama' => $jabatan,
                            'kategori' => null,
                            'keterangan' => null,
                            'is_active' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                $data['jabatan_id'] = $jabatanId;
            }
            if (Schema::hasColumn('users', 'no_hp')) {
                $data['no_hp'] = $noHp !== '' ? $noHp : null;
            }
            if (Schema::hasColumn('users', 'unit')) {
                $data['unit'] = $unit !== '' ? $unit : 'kesekretariatan';
            }
            if (Schema::hasColumn('users', 'bidang')) {
                $data['bidang'] = $bidang !== '' ? $bidang : null;
            }
            if (Schema::hasColumn('users', 'tingkatan')) {
                $data['tingkatan'] = $tingkatan;
            }
            if (Schema::hasColumn('users', 'hirarki')) {
                $data['hirarki'] = $hirarki;
            }

            DB::table('users')->updateOrInsert(['email' => $email], $data);
        }

        fclose($handle);
    }
}
