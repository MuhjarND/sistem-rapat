<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PublicAbsensiController extends Controller
{
    /**
     * Tampilkan halaman absensi publik.
     */
    public function show($token, Request $req)
    {
        $rapat = DB::table('rapat')->where('public_code', $token)->first();
        if (!$rapat) abort(404, 'Rapat tidak ditemukan atau link tidak valid.');
        if (isset($rapat->status) && strtolower($rapat->status) === 'dibatalkan') {
            return redirect()->route('home')->with('error', 'Rapat ini dibatalkan.');
        }

        $kategori = DB::table('kategori_rapat')->where('id', $rapat->id_kategori)->value('nama');

        $hadirCount = DB::table('absensi')
            ->where('id_rapat', $rapat->id)
            ->where('status', 'hadir')
            ->count();

        return view('absensi.publik', [
            'rapat'      => $rapat,
            'nama_kat'   => $kategori,
            'hadirCount' => $hadirCount,
            'allowNew'   => false,
        ]);
    }

    /**
     * Pencarian peserta untuk Select2 (kembalikan array datar [{id,text}, ...]).
     */
    public function search($token, Request $req)
    {
        try {
            $rapat = DB::table('rapat')->where('public_code', $token)->select('id')->first();
            if (!$rapat) {
                return response()->json([], 404);
            }

            $q       = trim($req->get('q', ''));
            $page    = (int) ($req->get('page', 1) ?: 1);
            $perPage = 20;
            $offset  = ($page - 1) * $perPage;

            $results = collect();

            // ===== Peserta internal (undangan -> users)
            $userQuery = DB::table('undangan as un')
                ->join('users as u', 'u.id', '=', 'un.id_user')
                ->where('un.id_rapat', $rapat->id);

            $select = ['u.id', 'u.name'];
            $hasJabatan = Schema::hasColumn('users', 'jabatan');
            $hasUnit    = Schema::hasColumn('users', 'unit');
            $hasBagian  = Schema::hasColumn('users', 'bagian');

            if ($hasJabatan) $select[] = 'u.jabatan';
            if ($hasUnit)    $select[] = 'u.unit';
            elseif ($hasBagian) $select[] = DB::raw('u.bagian as unit');

            if ($q !== '') {
                $userQuery->where(function ($s) use ($q, $hasJabatan, $hasUnit, $hasBagian) {
                    $s->where('u.name', 'like', "%{$q}%");
                    if ($hasJabatan) $s->orWhere('u.jabatan', 'like', "%{$q}%");
                    if ($hasUnit)    $s->orWhere('u.unit', 'like', "%{$q}%");
                    if (!$hasUnit && $hasBagian) $s->orWhere('u.bagian', 'like', "%{$q}%");
                });
            }

            $users = $userQuery->select($select)
                ->orderBy('u.name')
                ->offset($offset)->limit($perPage)
                ->get()
                ->map(function ($r) {
                    $jab = property_exists($r,'jabatan') ? ($r->jabatan ?? '-') : '-';
                    $sub = trim($jab . (($r->unit ?? '') ? ' · ' . $r->unit : ''));
                    return [
                        'id'   => 'user:' . $r->id,
                        'text' => trim(($r->name ?? '—') . ' — ' . $sub),
                    ];
                });

            $results = $results->merge($users);

            // ===== Tamu rapat (opsional)
            if (Schema::hasTable('tamu_rapat')) {
                $tamuQuery = DB::table('tamu_rapat as t')->where('t.id_rapat', $rapat->id);

                $hasJabTamu  = Schema::hasColumn('tamu_rapat', 'jabatan');
                $hasInstansi = Schema::hasColumn('tamu_rapat', 'instansi');

                if ($q !== '') {
                    $tamuQuery->where(function ($s) use ($q, $hasJabTamu, $hasInstansi) {
                        $s->where('t.nama', 'like', "%{$q}%");
                        if ($hasJabTamu)  $s->orWhere('t.jabatan',  'like', "%{$q}%");
                        if ($hasInstansi) $s->orWhere('t.instansi', 'like', "%{$q}%");
                    });
                }

                $tamuSelect = ['t.id', 't.nama'];
                if ($hasJabTamu)  $tamuSelect[] = 't.jabatan';
                if ($hasInstansi) $tamuSelect[] = 't.instansi';

                $tamu = $tamuQuery->select($tamuSelect)
                    ->orderBy('t.nama')
                    ->offset($offset)->limit($perPage)
                    ->get()
                    ->map(function ($r) use ($hasJabTamu, $hasInstansi) {
                        $jab   = $hasJabTamu  ? ($r->jabatan  ?? '-') : '-';
                        $inst  = $hasInstansi ? ($r->instansi ?? null) : null;
                        $sub   = trim($jab . ($inst ? ' · ' . $inst : ''));
                        return [
                            'id'   => 'tamu:' . $r->id,
                            'text' => trim(($r->nama ?? '—') . ' — ' . $sub),
                        ];
                    });

                $results = $results->merge($tamu);
            }

            // Gabungkan, unik, sort, dan batasi perPage (Select2 mumpuni menangani paginasi sisi klien)
            $results = $results->unique('text')->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)->values();
            if ($results->count() > $perPage) {
                $results = $results->slice(0, $perPage)->values();
            }

            // Kembalikan ARRAY DATAR
            return response()->json($results);

        } catch (\Throwable $e) {
            Log::error('PublicAbsensiController@search error', [
                'msg' => $e->getMessage(),
                'file'=> $e->getFile(),
                'line'=> $e->getLine(),
            ]);
            return response()->json([], 500);
        }
    }

    /**
     * Simpan absensi (publik).
     * - Simpan TTD ke public/ttd (bukan storage)
     * - Catat waktu_absen
     * - No. HP opsional: validasi + kirim notifikasi WA jika diisi
     */
    public function store($token, Request $req)
    {
        $rapat = DB::table('rapat')->where('public_code', $token)->first();
        if (!$rapat) return back()->with('error', 'Rapat tidak ditemukan.');

        $req->validate([
            'peserta' => 'required|string',
            'ttd'     => 'required|string',
            'no_hp'   => ['nullable','regex:/^0[0-9]{9,13}$/'],
        ],[
            'peserta.required' => 'Silakan pilih nama pada daftar.',
            'ttd.required'     => 'Tanda tangan belum diisi.',
            'no_hp.regex'      => 'Nomor HP tidak valid. Contoh: 081234567890',
        ]);

        $pesertaRaw = $req->input('peserta');
        if (!preg_match('/^(user|tamu):\d+$/', $pesertaRaw)) {
            return back()->with('error', 'Pilihan peserta tidak valid.')->withInput();
        }

        // ===== Mapping kolom dinamis tabel absensi
        $colUser = Schema::hasColumn('absensi', 'user_id') ? 'user_id'
                  : (Schema::hasColumn('absensi', 'id_user') ? 'id_user' : null);
        $colTamu = Schema::hasColumn('absensi', 'tamu_id') ? 'tamu_id'
                  : (Schema::hasColumn('absensi', 'id_tamu') ? 'id_tamu' : null);

        $colNama    = Schema::hasColumn('absensi', 'nama') ? 'nama'
                    : (Schema::hasColumn('absensi', 'name') ? 'name' : null);
        $colJabatan = Schema::hasColumn('absensi', 'jabatan') ? 'jabatan'
                    : (Schema::hasColumn('absensi', 'position') ? 'position' : null);
        $colUnit    = Schema::hasColumn('absensi', 'unit') ? 'unit'
                    : (Schema::hasColumn('absensi', 'satuan_kerja') ? 'satuan_kerja'
                    : (Schema::hasColumn('absensi', 'instansi') ? 'instansi' : null));
        $colNoHp    = Schema::hasColumn('absensi', 'no_hp') ? 'no_hp' : null;
        $colWaktu   = Schema::hasColumn('absensi', 'waktu_absen') ? 'waktu_absen' : null;

        [$type, $pid] = explode(':', $pesertaRaw, 2);
        $pid = (int) $pid;

        // Snapshot identitas
        $identitas = ['nama'=>null,'jabatan'=>null,'unit'=>null];
        $userId = null; $tamuId = null;

        if ($type === 'user') {
            $row = DB::table('users')->where('id', $pid)->first();
            if (!$row) return back()->with('error', 'Pengguna tidak ditemukan.');
            $identitas['nama']    = $row->name ?? null;
            $identitas['jabatan'] = Schema::hasColumn('users','jabatan') ? ($row->jabatan ?? null) : null;
            $identitas['unit']    = Schema::hasColumn('users','unit') ? ($row->unit ?? null)
                                    : (Schema::hasColumn('users','bagian') ? ($row->bagian ?? null) : null);
            $userId = $row->id;
        } else {
            if (!Schema::hasTable('tamu_rapat')) {
                return back()->with('error', 'Data tamu tidak tersedia.')->withInput();
            }
            $row = DB::table('tamu_rapat')->where('id', $pid)->where('id_rapat', $rapat->id)->first();
            if (!$row) return back()->with('error', 'Tamu tidak ditemukan.');
            $identitas['nama']    = $row->nama ?? null;
            $identitas['jabatan'] = Schema::hasColumn('tamu_rapat','jabatan')  ? ($row->jabatan ?? null)  : null;
            $identitas['unit']    = Schema::hasColumn('tamu_rapat','instansi') ? ($row->instansi ?? null) : null;
            $tamuId = $row->id;
        }

        // ===== Cegah dobel absensi
        $exists = DB::table('absensi')
            ->where('id_rapat', $rapat->id)
            ->where('status', 'hadir');
        if ($userId && $colUser) $exists->where($colUser, $userId);
        if ($tamuId && $colTamu) $exists->where($colTamu, $tamuId);

        if ($exists->exists()) {
            return back()->with('error', 'Anda sudah tercatat hadir untuk rapat ini.');
        }

        // ===== Simpan TTD ke PUBLIC/ttd
        $dataUrl = $req->input('ttd');
        if (strpos($dataUrl, 'data:image/png;base64,') !== 0) {
            return back()->with('error', 'Format tanda tangan tidak valid.');
        }
        $base64 = substr($dataUrl, strlen('data:image/png;base64,'));
        $bin    = base64_decode($base64);
        if ($bin === false) return back()->with('error', 'Gagal memproses tanda tangan.');

        $publicDir = public_path('ttd');
        if (!is_dir($publicDir)) {
            @mkdir($publicDir, 0775, true);
        }

        $fname       = 'rapat'.$rapat->id.'-'.$type.$pid.'-'.date('Ymd_His').'-'.Str::random(6).'.png';
        $relPath     = 'ttd/'.$fname;                 // path relatif untuk disimpan di DB
        $absolute    = $publicDir.DIRECTORY_SEPARATOR.$fname;

        file_put_contents($absolute, $bin);

        // ===== Insert ke tabel absensi
        $now = now();
        $payload = [
            'id_rapat'   => $rapat->id,
            'status'     => 'hadir',
            'ttd_path'   => $relPath,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if ($colNama)    $payload[$colNama]    = $identitas['nama'];
        if ($colJabatan) $payload[$colJabatan] = $identitas['jabatan'];
        if ($colUnit)    $payload[$colUnit]    = $identitas['unit'];
        if ($colWaktu)   $payload[$colWaktu]   = $now;

        if ($userId && $colUser) $payload[$colUser] = $userId;
        if ($tamuId && $colTamu) $payload[$colTamu] = $tamuId;

        // No. HP opsional
        $no_hp = $req->filled('no_hp') ? preg_replace('/[^0-9]/', '', $req->no_hp) : null;
        if ($no_hp && $colNoHp) {
            $payload[$colNoHp] = $no_hp;
        }

        DB::table('absensi')->insert($payload);

        // ===== Kirim notifikasi WA jika no_hp diisi (gunakan helper yang sama seperti RapatController)
// ===== Kirim notifikasi WA jika no_hp diisi (gunakan helper yang sama seperti RapatController)
        if ($no_hp) {
            try {
                $wa = preg_replace('/^0/', '62', $no_hp);
                $judul = $rapat->judul ?: 'Rapat';
                $tgl   = $rapat->tanggal ? \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') : '-';
                $wkt   = $rapat->waktu_mulai ?: '-';
                $tempat = $rapat->tempat ?: '-';

                $msg =
                    "*Assalamu’alaikum Warahmatullahi Wabarakatuh*\n\n" .
                    "Dengan hormat, kami informasikan bahwa kehadiran Anda telah *berhasil tercatat* pada kegiatan berikut:\n\n" .
                    "📌 *{$judul}*\n" .
                    "📅 {$tgl}\n" .
                    "⏰ {$wkt} WIT\n" .
                    "🏢 {$tempat}\n\n" .
                    "Terima kasih atas partisipasi dan kehadiran Bapak/Ibu.\n" .
                    "Semoga Allah SWT senantiasa memberikan keberkahan dan kelancaran dalam setiap aktivitas kita.\n\n" .
                    "Wassalamu’alaikum Warahmatullahi Wabarakatuh.\n\n" .
                    "— *Sistem Absensi Online PTA Papua Barat*";

                if (class_exists(\App\Helpers\FonnteWa::class)) {
                    \App\Helpers\FonnteWa::send($wa, $msg);
                }
            } catch (\Throwable $e) {
                Log::warning('Gagal kirim WA konfirmasi absensi publik', [
                    'hp'  => $no_hp,
                    'err' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('absensi.publik.show', $token)
            ->with('success', 'Terima kasih! Absensi Anda sudah tercatat.');
    }
}
