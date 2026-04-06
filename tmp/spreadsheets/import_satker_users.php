<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$sourcePath = 'H:\\My Drive\\TASK HARIAN\\DATA PEGAWAI\\smart app\\data user satker fix.xlsx';
$defaultDomain = 'pta-papuabarat.go.id';

function read_xlsx_rows(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Gagal membuka file Excel: ' . $path);
    }

    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sx = simplexml_load_string($sharedXml);
        if ($sx && isset($sx->si)) {
            foreach ($sx->si as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text = (string) $si->t;
                } else {
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                }
                $shared[] = trim($text);
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        throw new RuntimeException('Sheet1 tidak ditemukan.');
    }
    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet || !isset($sheet->sheetData->row)) {
        throw new RuntimeException('Isi sheet tidak valid.');
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $vals = [];
        foreach ($row->c as $c) {
            $ref = (string) $c['r'];
            preg_match('/([A-Z]+)/', $ref, $m);
            $col = $m[1] ?? '';
            $value = isset($c->v) ? (string) $c->v : '';
            $type = (string) $c['t'];

            if ($type === 's') {
                $value = $shared[(int) $value] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = isset($c->is->t) ? (string) $c->is->t : '';
            }

            $vals[$col] = trim($value);
        }
        $rows[] = $vals;
    }

    $zip->close();
    return $rows;
}

function slug_name_without_titles(string $name): string
{
    $value = trim($name);

    if (str_contains($value, ',')) {
        $segments = explode(',', $value);
        $value = trim((string) $segments[0]);
    }

    $value = preg_replace('/^(drs?|dra|drg|prof|ir|h|hj|kh|k\.h)\.?\s+/iu', '', $value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', trim($value));

    return str_replace(' ', '', $value);
}

function normalize_phone(?string $phone): ?string
{
    $digits = preg_replace('/\D+/', '', (string) $phone);
    return $digits !== '' ? $digits : null;
}

function normalize_role(string $role): string
{
    $role = strtolower(trim($role));
    $map = [
        'peserta' => 'peserta',
        'approval' => 'approval',
        'admin' => 'admin',
        'operator' => 'operator',
        'notulis' => 'notulis',
        'protokoler' => 'protokoler',
    ];

    return $map[$role] ?? 'peserta';
}

$rows = read_xlsx_rows($sourcePath);
if (count($rows) <= 1) {
    throw new RuntimeException('Baris data tidak ditemukan di file Excel.');
}

$headers = $rows[0];
$map = [];
foreach ($headers as $col => $title) {
    $map[strtolower(trim($title))] = $col;
}

$requiredHeaders = ['nama', 'no_hp', 'unit', 'jabatan', 'role', 'hirarki'];
foreach ($requiredHeaders as $header) {
    if (!isset($map[$header])) {
        throw new RuntimeException('Header wajib tidak ditemukan: ' . $header);
    }
}

$stats = [
    'processed' => 0,
    'inserted' => 0,
    'updated' => 0,
    'units_created' => 0,
    'jabatans_created' => 0,
    'skipped' => 0,
];

$usedEmails = DB::table('users')->pluck('email')->map(function ($email) {
    return strtolower((string) $email);
})->flip()->all();

DB::beginTransaction();
try {
    foreach (array_slice($rows, 1) as $row) {
        $name = trim((string) ($row[$map['nama']] ?? ''));
        $unit = trim((string) ($row[$map['unit']] ?? ''));
        $jabatan = trim((string) ($row[$map['jabatan']] ?? ''));

        if ($name === '' || $unit === '' || $jabatan === '') {
            $stats['skipped']++;
            continue;
        }

        $stats['processed']++;

        $role = normalize_role((string) ($row[$map['role']] ?? 'peserta'));
        $hirarki = trim((string) ($row[$map['hirarki']] ?? ''));
        $tingkatan = trim((string) ($row[$map['tingkatan']] ?? ''));
        $passwordRaw = trim((string) ($row[$map['password']] ?? ''));
        $noHp = normalize_phone((string) ($row[$map['no_hp']] ?? ''));

        $localPart = slug_name_without_titles($name);
        if ($localPart === '') {
            $localPart = 'user' . Str::random(6);
        }
        $email = $localPart . '@' . $defaultDomain;
        $baseEmail = $email;
        $suffix = 2;
        while (isset($usedEmails[strtolower($email)])) {
            $existingByEmail = DB::table('users')->where('email', $email)->first();
            if ($existingByEmail && strtolower(trim((string) $existingByEmail->name)) === strtolower($name)) {
                break;
            }
            $email = preg_replace('/@/', $suffix . '@', $baseEmail, 1);
            $suffix++;
        }
        $usedEmails[strtolower($email)] = true;

        $jabatanKeterangan = trim($unit . ' - ' . $jabatan);

        $unitRow = DB::table('units')->where('nama', $unit)->first();
        if (!$unitRow) {
            DB::table('units')->insert([
                'nama' => $unit,
                'singkatan' => null,
                'keterangan' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $stats['units_created']++;
        } elseif ((int) ($unitRow->is_active ?? 1) !== 1) {
            DB::table('units')->where('id', $unitRow->id)->update([
                'is_active' => 1,
                'updated_at' => now(),
            ]);
        }

        $jabatanRow = DB::table('jabatan')->where('nama', $jabatan)->first();
        if (!$jabatanRow) {
            DB::table('jabatan')->insert([
                'nama' => $jabatan,
                'kategori' => null,
                'keterangan' => $jabatanKeterangan,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $jabatanId = (int) DB::getPdo()->lastInsertId();
            $stats['jabatans_created']++;
        } else {
            $jabatanId = (int) $jabatanRow->id;
            DB::table('jabatan')->where('id', $jabatanId)->update([
                'is_active' => 1,
                'keterangan' => $jabatanKeterangan,
                'updated_at' => now(),
            ]);
        }

        $payload = [
            'name' => $name,
            'email' => $email,
            'no_hp' => $noHp,
            'jabatan' => $jabatan,
            'jabatan_id' => $jabatanId,
            'jabatan_keterangan' => $jabatanKeterangan,
            'unit' => $unit,
            'bidang' => null,
            'tingkatan' => $tingkatan !== '' ? (int) $tingkatan : null,
            'role' => $role,
            'hirarki' => $hirarki !== '' ? (int) $hirarki : null,
            'updated_at' => now(),
        ];

        if ($passwordRaw !== '') {
            $payload['password'] = Hash::make($passwordRaw);
        } else {
            $payload['password'] = Hash::make('ptapabar');
        }

        $existing = DB::table('users')
            ->where(function ($q) use ($email, $name, $unit) {
                $q->where('email', $email)
                  ->orWhere(function ($qq) use ($name, $unit) {
                      $qq->where('name', $name)->where('unit', $unit);
                  });
            })
            ->first();

        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update($payload);
            $stats['updated']++;
        } else {
            $payload['created_at'] = now();
            DB::table('users')->insert($payload);
            $stats['inserted']++;
        }
    }

    DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
