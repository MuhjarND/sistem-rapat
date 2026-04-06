<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SatkerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rows = [
            ['name' => 'Ahmad Rafdi Qastari, S.H.', 'no_hp' => '81340852418', 'unit' => 'PA Fak-Fak', 'jabatan' => 'Hakim', 'role' => 'peserta', 'hirarki' => 60, 'tingkatan' => null],
            ['name' => 'Dwi Anugerah, S.H.I., M.H.', 'no_hp' => '85238055666', 'unit' => 'PA Fak-Fak', 'jabatan' => 'Wakil Ketua', 'role' => 'peserta', 'hirarki' => 55, 'tingkatan' => null],
            ['name' => 'Lilis Marwati Achmad, S.H., M.H.', 'no_hp' => '81247441304', 'unit' => 'PA Fak-Fak', 'jabatan' => 'Sekretaris', 'role' => 'peserta', 'hirarki' => 64, 'tingkatan' => null],
            ['name' => 'Marwah, S.H., M.H.', 'no_hp' => '82397159948', 'unit' => 'PA Fak-Fak', 'jabatan' => 'Panitera', 'role' => 'peserta', 'hirarki' => 67, 'tingkatan' => null],
            ['name' => 'Muhammad Sopalatu, S.H.', 'no_hp' => '81248250302', 'unit' => 'PA Fak-Fak', 'jabatan' => 'Ketua', 'role' => 'peserta', 'hirarki' => 50, 'tingkatan' => null],
            ['name' => 'Abdul Rivai Rinom, S.H.I., M.H.', 'no_hp' => '82156225351', 'unit' => 'PA Kaimana', 'jabatan' => 'Hakim', 'role' => 'peserta', 'hirarki' => 57, 'tingkatan' => null],
            ['name' => 'Kiki Wulandari, S.H.', 'no_hp' => '82197734154', 'unit' => 'PA Kaimana', 'jabatan' => 'Hakim', 'role' => 'peserta', 'hirarki' => 61, 'tingkatan' => null],
            ['name' => 'Novia Dwi Kusumawati, S.H.', 'no_hp' => '81240621473', 'unit' => 'PA Kaimana', 'jabatan' => 'Panitera', 'role' => 'peserta', 'hirarki' => 65, 'tingkatan' => null],
            ['name' => 'Rustam Lengkas, S.H.I.', 'no_hp' => '82187017346', 'unit' => 'PA Kaimana', 'jabatan' => 'Sekretaris', 'role' => 'peserta', 'hirarki' => 63, 'tingkatan' => null],
            ['name' => 'Saiin Ngalim, S.H.I, M.M.', 'no_hp' => '85226994034', 'unit' => 'PA Kaimana', 'jabatan' => 'Ketua', 'role' => 'peserta', 'hirarki' => 51, 'tingkatan' => null],
            ['name' => 'Eddy Waluyo, S.E.', 'no_hp' => '81344928660', 'unit' => 'PA Manokwari', 'jabatan' => 'Sekretaris', 'role' => 'peserta', 'hirarki' => 58, 'tingkatan' => null],
            ['name' => 'Mohammad Abdul Kadir, S.Ag.', 'no_hp' => '8134879679', 'unit' => 'PA Manokwari', 'jabatan' => 'Panitera', 'role' => 'peserta', 'hirarki' => 56, 'tingkatan' => null],
            ['name' => 'Samsudin Djaki, S.H., M.H.', 'no_hp' => '82197261980', 'unit' => 'PA Manokwari', 'jabatan' => 'Ketua', 'role' => 'peserta', 'hirarki' => 53, 'tingkatan' => null],
            ['name' => 'Sudarmin H.I.M. Tang, S.H.I.,M.H', 'no_hp' => '85241137254', 'unit' => 'PA Manokwari', 'jabatan' => 'Wakil Ketua', 'role' => 'peserta', 'hirarki' => 54, 'tingkatan' => null],
            ['name' => 'Baida Makasar, S.Ag.', 'no_hp' => '82199118860', 'unit' => 'PA Sorong', 'jabatan' => 'Panitera', 'role' => 'peserta', 'hirarki' => 62, 'tingkatan' => null],
            ['name' => 'Doddy Armando Aska Assegaff, S.H.', 'no_hp' => '85244002291', 'unit' => 'PA Sorong', 'jabatan' => 'Sekretaris', 'role' => 'peserta', 'hirarki' => 66, 'tingkatan' => null],
            ['name' => 'Juandi Mardin, S.H.', 'no_hp' => '81355586235', 'unit' => 'PA Sorong', 'jabatan' => 'Hakim', 'role' => 'peserta', 'hirarki' => 59, 'tingkatan' => null],
            ['name' => 'Marwan Ibrahim Piinga, S.Ag., M.H.', 'no_hp' => '85298539909', 'unit' => 'PA Sorong', 'jabatan' => 'Ketua', 'role' => 'peserta', 'hirarki' => 52, 'tingkatan' => null],
        ];

        foreach ($rows as $row) {
            $email = $this->generateEmail($row['name']);
            $jabatanKeterangan = $row['unit'] . ' - ' . $row['jabatan'];

            $this->ensureUnitExists($row['unit']);
            $jabatanId = $this->ensureJabatanExists($row['jabatan']);

            $payload = [
                'name' => $row['name'],
                'email' => $email,
                'no_hp' => $this->normalizePhone($row['no_hp']),
                'jabatan' => $row['jabatan'],
                'jabatan_id' => $jabatanId,
                'jabatan_keterangan' => $jabatanKeterangan,
                'unit' => $row['unit'],
                'bidang' => null,
                'tingkatan' => $row['tingkatan'],
                'role' => $row['role'],
                'hirarki' => $row['hirarki'],
                'password' => Hash::make('ptapabar'),
                'updated_at' => now(),
            ];

            $existing = DB::table('users')
                ->where('email', $email)
                ->orWhere(function ($query) use ($row) {
                    $query->where('name', $row['name'])
                        ->where('unit', $row['unit']);
                })
                ->first();

            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update($payload);
                continue;
            }

            $payload['created_at'] = now();
            DB::table('users')->insert($payload);
        }
    }

    protected function ensureUnitExists(string $unit): void
    {
        $existing = DB::table('units')->where('nama', $unit)->first();

        if ($existing) {
            if ((int) ($existing->is_active ?? 1) !== 1) {
                DB::table('units')->where('id', $existing->id)->update([
                    'is_active' => 1,
                    'updated_at' => now(),
                ]);
            }

            return;
        }

        DB::table('units')->insert([
            'nama' => $unit,
            'singkatan' => null,
            'keterangan' => null,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function ensureJabatanExists(string $jabatan): int
    {
        $existing = DB::table('jabatan')->where('nama', $jabatan)->first();

        if ($existing) {
            if ((int) ($existing->is_active ?? 1) !== 1) {
                DB::table('jabatan')->where('id', $existing->id)->update([
                    'is_active' => 1,
                    'updated_at' => now(),
                ]);
            }

            return (int) $existing->id;
        }

        DB::table('jabatan')->insert([
            'nama' => $jabatan,
            'kategori' => null,
            'keterangan' => $jabatan,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::getPdo()->lastInsertId();
    }

    protected function generateEmail(string $name): string
    {
        $value = trim($name);

        if (strpos($value, ',') !== false) {
            $segments = explode(',', $value);
            $value = trim((string) $segments[0]);
        }

        $value = preg_replace('/^(drs?|dra|drg|prof|ir|h|hj|kh|k\.h)\.?\s+/iu', '', $value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', trim($value));

        return str_replace(' ', '', $value) . '@pta-papuabarat.go.id';
    }

    protected function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits !== '' ? $digits : null;
    }
}
