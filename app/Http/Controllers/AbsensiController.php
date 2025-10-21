<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    /**
     * Durasi jendela absensi (jam).
     * Bisa diubah via ENV: ABSENSI_DURATION_HOURS=3
     */
    private function getAbsensiDurationHours(): int
    {
        $val = (int) env('ABSENSI_DURATION_HOURS', 3);
        return $val > 0 ? $val : 3;
    }

    /**
     * Hitung jendela absensi berdasarkan tanggal & waktu_mulai rapat.
     * start = tanggal + waktu_mulai
     * end   = start + ABSENSI_DURATION_HOURS
     * open  = sekarang berada di antara start..end (inklusif)
     */
    private function getAbsensiWindow(\stdClass $rapat): array
    {
        $window = (int) (config('absensi.window_minutes', env('ABSENSI_WINDOW_MINUTES', 180)));
        if ($window <= 0) $window = 180;

        $start = \Carbon\Carbon::parse($rapat->tanggal . ' ' . $rapat->waktu_mulai);
        $end   = (clone $start)->addMinutes($window);
        $now   = now();

        $before = $now->lt($start);
        $after  = $now->gt($end);
        $open   = !$before && !$after;

        return [
            'start'          => $start,
            'end'            => $end,
            'open'           => $open,
            'before'         => $before,
            'after'          => $after,
            'window_minutes' => $window,
        ];
    }

    /**
     * Admin: Daftar rapat untuk pengelolaan absensi
     */
    public function index(Request $request)
    {
        $perPage = 6;

        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        $q = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->leftJoin('users as pembuat', 'rapat.dibuat_oleh', '=', 'pembuat.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'pembuat.name as nama_pembuat'
            );

        if ($request->filled('kategori')) {
            $q->where('rapat.id_kategori', $request->kategori);
        }
        if ($request->filled('tanggal')) {
            $q->whereDate('rapat.tanggal', $request->tanggal);
        }
        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $q->where(function ($qq) use ($kw) {
                $qq->where('rapat.judul', 'like', "%{$kw}%")
                   ->orWhere('rapat.nomor_undangan', 'like', "%{$kw}%");
            });
        }

        $daftar_rapat = $q->orderBy('rapat.tanggal', 'desc')
            ->orderBy('rapat.waktu_mulai', 'desc')
            ->paginate($perPage)
            ->appends($request->query());

        $ids = $daftar_rapat->pluck('id')->all();
        $peserta_map = [];
        if (!empty($ids)) {
            $peserta_map = DB::table('undangan')
                ->select('id_rapat', DB::raw('COUNT(*) as jml'))
                ->whereIn('id_rapat', $ids)
                ->groupBy('id_rapat')
                ->pluck('jml', 'id_rapat');
        }
        foreach ($daftar_rapat as $r) {
            $r->jumlah_peserta = $peserta_map[$r->id] ?? 0;
        }

        $filter = [
            'kategori' => $request->kategori,
            'tanggal'  => $request->tanggal,
            'keyword'  => $request->keyword,
        ];

        return view('absensi.index', compact('daftar_rapat', 'daftar_kategori', 'filter'));
    }

    public function create()
    {
        $peserta = DB::table('users')->where('role', 'peserta')->orderBy('name')->get();
        $rapat   = DB::table('rapat')->orderBy('tanggal', 'desc')->orderBy('waktu_mulai', 'desc')->get();
        return view('absensi.create', compact('peserta', 'rapat'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_rapat' => 'required|exists:rapat,id',
            'id_user'  => 'required|exists:users,id',
            'status'   => 'required|in:hadir,izin,alfa',
        ]);

        $ada = DB::table('absensi')
            ->where('id_rapat', $request->id_rapat)
            ->where('id_user',  $request->id_user)
            ->exists();

        if ($ada) {
            return redirect()->back()->with('error', 'Peserta sudah mengisi absensi untuk rapat ini.');
        }

        DB::table('absensi')->insert([
            'id_rapat'    => $request->id_rapat,
            'id_user'     => $request->id_user,
            'status'      => $request->status,
            'waktu_absen' => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->route('absensi.index')->with('success', 'Absensi berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $absensi = DB::table('absensi')->where('id', $id)->first();
        $peserta = DB::table('users')->where('role', 'peserta')->orderBy('name')->get();
        $rapat   = DB::table('rapat')->orderBy('tanggal', 'desc')->orderBy('waktu_mulai', 'desc')->get();
        return view('absensi.edit', compact('absensi', 'peserta', 'rapat'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_rapat' => 'required|exists:rapat,id',
            'id_user'  => 'required|exists:users,id',
            'status'   => 'required|in:hadir,izin,alfa',
        ]);

        DB::table('absensi')->where('id', $id)->update([
            'id_rapat'    => $request->id_rapat,
            'id_user'     => $request->id_user,
            'status'      => $request->status,
            'waktu_absen' => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->route('absensi.index')->with('success', 'Absensi berhasil diubah.');
    }

    public function destroy($id)
    {
        DB::table('absensi')->where('id', $id)->delete();
        return redirect()->route('absensi.index')->with('success', 'Absensi berhasil dihapus.');
    }

    public function absensiSaya()
    {
        $absensi = DB::table('absensi')
            ->join('rapat', 'absensi.id_rapat', '=', 'rapat.id')
            ->where('absensi.id_user', Auth::id())
            ->select('absensi.*', 'rapat.judul', 'rapat.tanggal', 'rapat.tempat')
            ->orderBy('rapat.tanggal', 'desc')
            ->orderBy('rapat.waktu_mulai', 'desc')
            ->get();

        $undangan = DB::table('undangan')
            ->join('rapat', 'undangan.id_rapat', '=', 'rapat.id')
            ->where('undangan.id_user', Auth::id())
            ->whereNotIn('undangan.id_rapat', function ($q) {
                $q->select('id_rapat')->from('absensi')->where('id_user', Auth::id());
            })
            ->select('undangan.*', 'rapat.judul', 'rapat.tanggal', 'rapat.tempat')
            ->orderBy('rapat.tanggal', 'desc')
            ->orderBy('rapat.waktu_mulai', 'desc')
            ->get();

        return view('absensi.saya', compact('absensi', 'undangan'));
    }

    /** ================== SCAN (login user internal) ================== */
    public function scan($token)
    {
        $rapat = DB::table('rapat')->where('token_qr', $token)->first();
        if (!$rapat) abort(404);

        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login untuk absen.');
        }

        $diundang = DB::table('undangan')
            ->where('id_rapat', $rapat->id)
            ->where('id_user', Auth::id())
            ->exists();

        if (!$diundang) {
            return redirect()->route('home')->with('error', 'Anda tidak terdaftar pada rapat ini.');
        }

        $sudah_absen = DB::table('absensi')
            ->where('id_rapat', $rapat->id)
            ->where('id_user', Auth::id())
            ->exists();

        // === status jendela absensi (mulai-selesai) ===
        $win = $this->getAbsensiWindow($rapat);

        return view('absensi.scan', [
            'rapat'               => $rapat,
            'sudah_absen'         => $sudah_absen,
            'abs_start'           => $win['start'],           // Carbon
            'abs_end'             => $win['end'],             // Carbon
            'abs_open'            => $win['open'],            // bool
            'abs_before'          => $win['before'],          // bool
            'abs_after'           => $win['after'],           // bool
            'abs_window_minutes'  => $win['window_minutes'],  // int (menit)
        ]);
    }

    public function simpanScan(Request $request, $token)
    {
        $rapat = DB::table('rapat')->where('token_qr', $token)->first();
        if (!$rapat) abort(404);

        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login untuk absen.');
        }

        $diundang = DB::table('undangan')
            ->where('id_rapat', $rapat->id)
            ->where('id_user', Auth::id())
            ->exists();

        if (!$diundang) {
            return redirect()->route('home')->with('error', 'Anda tidak terdaftar pada rapat ini.');
        }

        // >>> BATAS WAKTU ABSENSI
        $win = $this->getAbsensiWindow($rapat);
        if (!$win['open']) {
            $mulai = $win['start']->isoFormat('D MMM Y HH:mm');
            $akhir = $win['end']->isoFormat('D MMM Y HH:mm');
            return back()->with('error', "Absensi hanya dibuka pada rentang {$mulai} s/d {$akhir}.");
        }

        // === VALIDASI SIGNATURE ===
        $request->validate([
            'signature_data' => 'required|string' // data:image/png;base64,....
        ],[
            'signature_data.required' => 'Tanda tangan belum diisi.'
        ]);

        $dataUrl = $request->input('signature_data');
        if (!preg_match('#^data:image/png;base64,#', $dataUrl)) {
            return back()->with('error','Format tanda tangan tidak valid.');
        }
        $base64 = substr($dataUrl, strpos($dataUrl, ',')+1);
        $bin = base64_decode($base64, true);
        if ($bin === false || strlen($bin) < 2000) { // minimal ~2KB biar tidak kosong
            return back()->with('error','Data tanda tangan tidak valid/terlalu kecil.');
        }

        // === SIMPAN FILE PNG ===
        $dir = public_path('uploads/ttd');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $filename = 'ttd_r'.$rapat->id.'_u'.Auth::id().'_'.date('Ymd_His').'_'.Str::random(6).'.png';
        $pathRel  = 'uploads/ttd/'.$filename;
        $pathAbs  = public_path($pathRel);
        file_put_contents($pathAbs, $bin);

        // hash untuk audit
        $hash = hash('sha256', $bin);

        // === UPSERT ABSENSI ===
        $exists = DB::table('absensi')
            ->where('id_rapat', $rapat->id)
            ->where('id_user',  Auth::id())
            ->exists();

        $payloadUpdate = [
            'status'         => 'hadir',
            'waktu_absen'    => now(),
            'ttd_path'       => $pathRel,
            'ttd_hash'       => $hash,
            'ttd_user_agent' => substr($request->input('ua', $request->header('User-Agent', '')), 0, 255),
            'ttd_timezone'   => substr($request->input('tz',''), 0, 64),
            'updated_at'     => now(),
        ];

        if ($exists) {
            DB::table('absensi')
                ->where('id_rapat', $rapat->id)
                ->where('id_user',  Auth::id())
                ->update($payloadUpdate);
        } else {
            DB::table('absensi')->insert(array_merge($payloadUpdate, [
                'id_rapat'   => $rapat->id,
                'id_user'    => Auth::id(),
                'created_at' => now(),
            ]));
        }

        // === NOTIFIKASI WA (user internal) ===
        $this->notifyAbsensiWa(Auth::id(), $rapat, 'hadir');

        return redirect()->route('absensi.scan', $token)->with('success', 'Absensi (TTD) berhasil direkam. Terima kasih!');
    }

    /** ================== GUEST ACCESS (tanpa login) ================== */

    /** Form Absensi Tamu */
    public function guestForm($rapatId, $token, Request $request)
    {
        $rapat = DB::table('rapat')->where('id', $rapatId)->first();
        if (!$rapat || empty($rapat->guest_token) || !hash_equals((string)$rapat->guest_token, (string)$token)) {
            abort(403, 'Token tidak valid.');
        }

        // Opsional: batasi masa aktif form tamu
        // $win = $this->getAbsensiWindow($rapat);
        // if (!$win['open']) abort(403,'Masa absensi tamu telah berakhir.');

        return view('absensi.guest_form', compact('rapat','token'));
    }

    /** Submit Absensi Tamu + simpan TTD (PNG) */
    public function guestSubmit($rapatId, $token, Request $request)
    {
        $rapat = DB::table('rapat')->where('id', $rapatId)->first();
        if (!$rapat || empty($rapat->guest_token) || !hash_equals((string)$rapat->guest_token, (string)$token)) {
            return back()->with('error','Token tidak valid.')->withInput();
        }

        $request->validate([
            'nama'     => 'required|string|max:120',
            'instansi' => 'nullable|string|max:150',
            'jabatan'  => 'nullable|string|max:120',
            'no_hp'    => 'nullable|regex:/^0[0-9]{9,13}$/',
            'status'   => 'nullable|in:hadir,tidak_hadir,izin',
            'ttd_data' => 'nullable|string', // data:image/png;base64,....
        ],[
            'no_hp.regex' => 'Format no HP tidak valid (harus diawali 0).'
        ]);

        // Simpan TTD (opsional)
        $ttdPath = null; $ttdHash = null;
        if ($request->filled('ttd_data') && str_starts_with($request->ttd_data, 'data:image/png;base64,')) {
            $raw = base64_decode(substr($request->ttd_data, 22));
            if ($raw !== false && strlen($raw) > 0) {
                @is_dir(public_path('ttd')) || @mkdir(public_path('ttd'), 0755, true);
                $fname = 'ttd/guest_'.$rapatId.'_'.Str::random(8).'.png';
                file_put_contents(public_path($fname), $raw);
                $ttdPath = $fname;
                $ttdHash = hash('sha256', $raw);
            }
        }

        DB::table('absensi_guest')->insert([
            'id_rapat'     => $rapatId,
            'nama'         => $request->nama,
            'instansi'     => $request->instansi,
            'jabatan'      => $request->jabatan,
            'no_hp'        => $request->no_hp,
            'status'       => $request->input('status','hadir'),
            'waktu_absen'  => now(),
            'ttd_path'     => $ttdPath,
            'ttd_hash'     => $ttdHash,
            'ip'           => $request->ip(),
            'user_agent'   => substr($request->userAgent() ?? '', 255),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // WA ke tamu (jika isi No HP)
        $this->notifyAbsensiWaGuest($request->no_hp, $rapat, $request->input('status','hadir'), $request->nama);

        return redirect()->route('absensi.guest.form', [$rapatId, $token])
            ->with('ok','Terima kasih, absensi tamu berhasil terekam.');
    }

    /**
     * Jika semua approval undangan sudah approved, buat 1 QR ABSENSI (unik & beda).
     * QR disimpan ke public/qr dan di-embed logo (PNG transparan) di tengah tanpa package.
     */
    public function ensureAbsensiQrMirrorsUndangan(int $rapatId): void
    {
        $rapat = DB::table('rapat')->where('id', $rapatId)->first();
        if (!$rapat || !$rapat->approval1_user_id) return;

        // 1) semua step UNDANGAN harus approved
        $steps = DB::table('approval_requests')
            ->where('rapat_id', $rapatId)
            ->where('doc_type', 'undangan')
            ->orderBy('order_index')
            ->get();

        if ($steps->isEmpty()) return;

        $allApproved = $steps->every(function($s){ return $s->status === 'approved'; });
        if (!$allApproved) return;

        // 2) cek apakah QR absensi sudah ada
        $absensiRow = DB::table('approval_requests')
            ->where('rapat_id', $rapatId)
            ->where('doc_type', 'absensi')
            ->where('approver_user_id', $rapat->approval1_user_id)
            ->first();

        if ($absensiRow && $absensiRow->status === 'approved' && $absensiRow->signature_qr_path) {
            return;
        }

        // 3) payload unik absensi
        $approver = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
        $chainSig = hash('sha256', collect($steps)->pluck('signature_payload')->filter()->join('|'));

        $payload = [
            'v'        => 1,
            'doc_type' => 'absensi',
            'rapat_id' => $rapat->id,
            'nomor'    => $rapat->nomor_undangan ?? null,
            'judul'    => $rapat->judul,
            'tanggal'  => $rapat->tanggal,
            'derived'  => [
                'from'      => 'undangan',
                'chain_sig' => $chainSig,
            ],
            'approver' => [
                'id'      => $approver->id ?? null,
                'name'    => $approver->name ?? null,
                'jabatan' => $approver->jabatan ?? null,
                'role'    => 'final',
            ],
            'issued_at'=> now()->toIso8601String(),
            'nonce'    => Str::random(18),
        ];

        $secret = config('app.key');
        if (is_string($secret) && Str::startsWith($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }
        $payload['sig'] = hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            $secret
        );
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        // 4) buat QR (isi = URL verifikasi)
        $qrContent = route('qr.verify', ['d' => base64_encode($payloadJson)]);
        $encoded   = urlencode($qrContent);
        $qrUrl     = "https://chart.googleapis.com/chart?chs=600x600&cht=qr&chl={$encoded}";

        $qrDir = public_path('qr');
        if (!is_dir($qrDir)) @mkdir($qrDir, 0755, true);

        $filename     = 'qr_absensi_r'.$rapat->id.'_a'.($approver->id ?? '0').'_'.Str::random(6).'.png';
        $relativePath = 'qr/'.$filename;
        $absolutePath = public_path($relativePath);

        $pngData = @file_get_contents($qrUrl);
        if ($pngData === false) {
            $alt = "https://api.qrserver.com/v1/create-qr-code/?size=600x600&data={$encoded}";
            $pngData = @file_get_contents($alt);
        }
        if ($pngData === false) {
            Log::warning('[absensi-qr] gagal unduh QR untuk rapat '.$rapat->id);
            return;
        }

        // 4b) tempelkan logo di tengah (jika GD tersedia & logo ada)
        $logoPath = public_path('logo_qr.png'); // sesuaikan path logo Anda
        $saved = false;
        if (function_exists('imagecreatefromstring') && function_exists('imagepng')) {
            $qrImg = @imagecreatefromstring($pngData);
            if ($qrImg !== false) {
                if (is_file($logoPath)) {
                    $logoImg = @imagecreatefrompng($logoPath);
                    if ($logoImg !== false) {
                        imagealphablending($logoImg, true);
                        imagesavealpha($logoImg, true);

                        $qrW   = imagesx($qrImg);
                        $qrH   = imagesy($qrImg);
                        $logoW = imagesx($logoImg);
                        $logoH = imagesy($logoImg);

                        $targetW = (int) round($qrW * 0.20);
                        $targetH = (int) round($logoH * ($targetW / $logoW));

                        $dstX = (int) round(($qrW - $targetW) / 2);
                        $dstY = (int) round(($qrH - $targetH) / 2);

                        $logoResized = imagecreatetruecolor($targetW, $targetH);
                        imagealphablending($logoResized, false);
                        imagesavealpha($logoResized, true);
                        imagecopyresampled($logoResized, $logoImg, 0, 0, 0, 0, $targetW, $targetH, $logoW, $logoH);

                        imagecopy($qrImg, $logoResized, $dstX, $dstY, 0, 0, $targetW, $targetH);

                        imagepng($qrImg, $absolutePath);
                        imagedestroy($logoResized);
                        imagedestroy($logoImg);
                        imagedestroy($qrImg);
                        $saved = true;
                    }
                }
                if (!$saved) {
                    imagepng($qrImg, $absolutePath);
                    imagedestroy($qrImg);
                    $saved = true;
                }
            }
        }

        if (!$saved) {
            file_put_contents($absolutePath, $pngData);
        }

        // 5) upsert approval_requests absensi
        if ($absensiRow) {
            DB::table('approval_requests')->where('id', $absensiRow->id)->update([
                'status'            => 'approved',
                'signature_qr_path' => $relativePath,
                'signature_payload' => $payloadJson,
                'signed_at'         => now(),
                'updated_at'        => now(),
            ]);
        } else {
            DB::table('approval_requests')->insert([
                'rapat_id'          => $rapat->id,
                'doc_type'          => 'absensi',
                'approver_user_id'  => $rapat->approval1_user_id,
                'order_index'       => 1,
                'status'            => 'approved',
                'sign_token'        => Str::random(32),
                'signature_qr_path' => $relativePath,
                'signature_payload' => $payloadJson,
                'signed_at'         => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    /**
     * Export PDF Laporan Absensi 1 rapat
     */
public function exportPdf(Request $request, $id_rapat)
{
    // Pastikan QR absensi sudah ada & up-to-date
    $this->ensureAbsensiQrMirrorsUndangan((int) $id_rapat);

    // ====== Data rapat + kategori
    $rapat = DB::table('rapat')
        ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
        ->select('rapat.*', 'kategori_rapat.nama as nama_kategori')
        ->where('rapat.id', $id_rapat)
        ->first();

    if (!$rapat) abort(404);

    // ====== Peserta internal (user) + status/ttd di absensi
    $pesertaUser = DB::table('undangan')
        ->join('users','users.id','=','undangan.id_user')
        ->leftJoin('absensi', function ($q) use ($id_rapat) {
            $q->on('absensi.id_user','=','undangan.id_user')
              ->where('absensi.id_rapat','=',$id_rapat);
        })
        ->where('undangan.id_rapat', $id_rapat)
        ->select(
            DB::raw("'user' as tipe"),
            'users.id as user_id',
            'users.name as nama',
            'users.jabatan',
            // pastikan ambil kolom unit (atau bagian yang sudah dipetakan sebelumnya)
            'users.unit',
            'absensi.status',
            'absensi.waktu_absen',
            'absensi.created_at as abs_created_at',
            'absensi.ttd_path',
            'absensi.ttd_hash',
            DB::raw('COALESCE(users.hirarki, 9999) as hirarki')
        );

    // ====== Peserta tamu (guest) dari absensi_guest
    $pesertaGuest = DB::table('absensi_guest')
        ->where('id_rapat',$id_rapat)
        ->select(
            DB::raw("'guest' as tipe"),
            DB::raw('NULL as user_id'),
            'nama',
            'jabatan',
            // peta instansi -> unit agar konsisten di laporan
            DB::raw('instansi as unit'),
            'status',
            'waktu_absen',
            'created_at as abs_created_at',
            'ttd_path',
            'ttd_hash',
            DB::raw('99999 as hirarki')
        );

    // ====== UNION dan ambil semua baris
    $pesertaRaw = $pesertaUser->unionAll($pesertaGuest)->get();

    // ====== Sort multi-kolom: hirarki ASC, lalu nama ASC (case-insensitive)
    $pesertaRaw = $pesertaRaw->sort(function ($a, $b) {
        $ha = (int) ($a->hirarki ?? 99999);
        $hb = (int) ($b->hirarki ?? 99999);
        if ($ha !== $hb) return $ha <=> $hb;
        return strcasecmp((string)($a->nama ?? ''), (string)($b->nama ?? ''));
    })->values();

    // ====== Normalisasi untuk Blade (array skalar) + embed TTD sebagai data:image
    $peserta = [];
    foreach ($pesertaRaw as $row) {
        // Build TTD data-uri dari file di public (mis: public/ttd/...)
        $ttdData = null;
        if (!empty($row->ttd_path)) {
            // dukung path absolut/relatif
            $path = $row->ttd_path;
            // jika url http, langsung pakai
            if (preg_match('~^https?://~i', $path)) {
                // DomPDF kadang membatasi remote URL; lebih aman tetap coba base64-kan
                try {
                    $bin = @file_get_contents($path);
                    if ($bin !== false) {
                        $ttdData = 'data:image/png;base64,' . base64_encode($bin);
                    }
                } catch (\Throwable $e) {
                    // fallback—biarkan null
                }
            } else {
                // relatif ke public_path
                $fs = public_path($path);
                if (!is_file($fs) && str_starts_with($path, '/')) {
                    $fs = public_path(ltrim($path,'/'));
                }
                if (is_file($fs)) {
                    $bin = @file_get_contents($fs);
                    if ($bin !== false) {
                        $ttdData = 'data:image/png;base64,' . base64_encode($bin);
                    }
                }
            }
        }

        // Waktu absen diformat WIT (Asia/Jayapura) => "D MMM Y HH:mm"
        $waktuAbs = $row->waktu_absen ?: $row->abs_created_at;
        $waktuAbsStr = null;
        if (!empty($waktuAbs)) {
            try {
                \Carbon\Carbon::setLocale('id');
                $waktuAbsStr = \Carbon\Carbon::parse($waktuAbs, 'Asia/Jayapura')
                                ->timezone('Asia/Jayapura')
                                ->isoFormat('D MMM Y HH:mm');
            } catch (\Throwable $e) {
                $waktuAbsStr = $waktuAbs;
            }
        }

        $peserta[] = [
            'name'        => (string) ($row->nama ?? ''),
            'jabatan'     => (string) ($row->jabatan ?? ''),
            'unit'        => (string) ($row->unit ?? ''),    // <-- penting untuk kolom Unit/Instansi
            'status'      => (string) ($row->status ?? ''),
            'waktu_absen' => $waktuAbsStr,                  // dipakai Blade jadi "TTD: ... WIT"
            'ttd_data'    => $ttdData,                      // aman untuk DomPDF
            // 'ttd_path'  => $row->ttd_path,                // tidak perlu, kita sudah embed base64
        ];
    }

    // ===== Rekap gabungan
    $rekap = [
        'diundang'    => (int) DB::table('undangan')->where('id_rapat',$id_rapat)->count()
                          + (int) DB::table('absensi_guest')->where('id_rapat',$id_rapat)->count(),
        'hadir'       => (int) DB::table('absensi')->where('id_rapat',$id_rapat)->where('status','hadir')->count()
                          + (int) DB::table('absensi_guest')->where('id_rapat',$id_rapat)->where('status','hadir')->count(),
        'tidak_hadir' => (int) DB::table('absensi')->where('id_rapat',$id_rapat)->where('status','tidak_hadir')->count()
                          + (int) DB::table('absensi_guest')->where('id_rapat',$id_rapat)->where('status','tidak_hadir')->count(),
        'izin'        => (int) DB::table('absensi')->where('id_rapat',$id_rapat)->where('status','izin')->count()
                          + (int) DB::table('absensi_guest')->where('id_rapat',$id_rapat)->where('status','izin')->count(),
    ];

    // ===== QR ABSENSI (final jika sudah approved)
    $absensiReq = DB::table('approval_requests')
        ->where('rapat_id', $id_rapat)
        ->where('doc_type', 'absensi')
        ->where('approver_user_id', $rapat->approval1_user_id)
        ->first();

    $qrSrc = null;
    if ($absensiReq && $absensiReq->signature_qr_path) {
        $qrPath = $absensiReq->signature_qr_path;
        $fs = public_path($qrPath);
        if (is_file($fs)) {
            // embed sebagai data:image untuk DomPDF
            $bin = @file_get_contents($fs);
            if ($bin !== false) {
                $qrSrc = 'data:image/png;base64,' . base64_encode($bin);
            }
        } elseif (preg_match('~^https?://~i', $qrPath)) {
            // remote url → coba embed
            try {
                $bin = @file_get_contents($qrPath);
                if ($bin !== false) {
                    $qrSrc = 'data:image/png;base64,' . base64_encode($bin);
                }
            } catch (\Throwable $e) { /* ignore */ }
        }
    }

    // ===== Approver final
    $approverFinal = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
    $approver = [
        'nama'    => (string) ($approverFinal->name ?? ''),
        'jabatan' => (string) ($approverFinal->jabatan ?? 'Penanggung Jawab'),
    ];

    // ===== Meta rapat (skalar)
    try { \Carbon\Carbon::setLocale('id'); } catch (\Throwable $e) {}
    $rap = [
        'nama_kategori' => (string) ($rapat->nama_kategori ?? '-'),
        'judul'         => (string) ($rapat->judul ?? '-'),
        'tanggal_human' => $rapat->tanggal
            ? \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y')
            : '-',
        'waktu_mulai'   => (string) ($rapat->waktu_mulai ?? ''),
        'tempat'        => (string) ($rapat->tempat ?? '-'),
    ];

    // ===== Render PDF
    $pdf = Pdf::loadView('absensi.laporan_pdf', [
        'rap'       => $rap,
        'peserta'   => $peserta,
        'rekap'     => $rekap,
        'qrSrc'     => $qrSrc,
        'approver'  => $approver,
        'kop'       => public_path('kop_absen.jpg'),
    ])->setPaper('A4', 'portrait');

    $filename = 'Laporan-Absensi-' . str_replace(' ', '-', $rap['judul']) . '.pdf';

    // === MODE: preview vs download
    if ($request->boolean('preview')) {
        return $pdf->stream($filename); // inline
    }
    return $pdf->download($filename);   // attachment
}


    /** ====================== Helper WA ======================= */

    private function normalizeMsisdn(?string $raw): ?string
    {
        if (!$raw) return null;
        $d = preg_replace('/\D+/', '', $raw);
        if ($d === '') return null;
        if (strpos($d, '62') === 0) return $d;
        if (strpos($d, '0') === 0)  return '62'.substr($d, 1);
        if (strpos($d, '8') === 0)  return '62'.$d;
        return $d;
    }

    // ——— Kirim via Fonnte
    private function sendWaFonnte(string $phone, string $message): bool
    {
        if (!filter_var(env('FONNTE_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) return false;
        $token = env('FONNTE_TOKEN');
        if (!$token) return false;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.fonnte.com/send',
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: {$token}"],
            CURLOPT_POSTFIELDS     => [
                'target'  => $phone,
                'message' => $message,
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $resp = curl_exec($ch);
        $ok   = !curl_errno($ch) && $resp !== false;
        curl_close($ch);
        return $ok;
    }

    // ——— Rakit & kirim pesan absensi (user internal)
    private function notifyAbsensiWa(int $userId, \stdClass $rapat, string $status): void
    {
        $user = DB::table('users')->where('id', $userId)->select('name','no_hp')->first();
        if (!$user) return;

        $msisdn = $this->normalizeMsisdn($user->no_hp ?? null);
        if (!$msisdn) return;

        Carbon::setLocale('id');
        $tgl = Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y');
        $sender = env('FONNTE_SENDER', 'Sistem Rapat');

        $msg = "*{$sender}*\n"
             . "Hai *{$user->name}*, terima kasih sudah mengisi absensi.\n\n"
             . "*Rapat*   : {$rapat->judul}\n"
             . "*Tanggal* : {$tgl}\n"
             . "*Waktu*   : {$rapat->waktu_mulai} WIT\n"
             . "*Tempat*  : {$rapat->tempat}\n"
             . "*Status*  : *".strtoupper($status)."*\n\n"
             . "_Pesan otomatis dari sistem._";

        try {
            $this->sendWaFonnte($msisdn, $msg);
        } catch (\Throwable $e) {
            // Log::warning('Fonnte error: '.$e->getMessage());
        }
    }

    // ——— Rakit & kirim pesan absensi (tamu)
    private function notifyAbsensiWaGuest(?string $noHp, \stdClass $rapat, string $status, string $nama): void
    {
        $msisdn = $this->normalizeMsisdn($noHp ?? null);
        if (!$msisdn) return;

        Carbon::setLocale('id');
        $tgl = Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y');
        $sender = env('FONNTE_SENDER', 'Sistem Rapat');

        $msg = "*{$sender}*\n"
             . "Terima kasih *{$nama}* sudah melakukan absensi.\n\n"
             . "*Rapat*   : {$rapat->judul}\n"
             . "*Tanggal* : {$tgl}\n"
             . "*Waktu*   : {$rapat->waktu_mulai} WIT\n"
             . "*Tempat*  : {$rapat->tempat}\n"
             . "*Status*  : *".strtoupper($status)."*\n\n"
             . "_Pesan otomatis dari sistem._";
        try {
            $this->sendWaFonnte($msisdn, $msg);
        } catch (\Throwable $e) {
            // Log::warning('Fonnte guest error: '.$e->getMessage());
        }
    }

    public function notifyStart(Request $request, int $rapatId)
    {
        $rapat = DB::table('rapat')->where('id', $rapatId)->first();
        if (!$rapat) return back()->with('error', 'Rapat tidak ditemukan.');

        $sendAll = $request->boolean('all'); // dari form hidden input name="all" value="1"

        // URL absensi internal (peserta)
        $absensiUrl = route('absensi.scan', $rapat->token_qr);

        // Target peserta internal (users yang diundang)
        $q = DB::table('undangan as u')
            ->join('users as usr', 'usr.id', '=', 'u.id_user')
            ->leftJoin('absensi as a', function($j) use ($rapatId){
                $j->on('a.id_user', '=', 'u.id_user')
                ->where('a.id_rapat', '=', $rapatId);
            })
            ->where('u.id_rapat', $rapatId)
            ->select(
                'usr.id',
                'usr.name',
                'usr.no_hp',
                'a.status as abs_status'
            );

        // Jika BUKAN "all", batasi hanya yang belum HADIR
        if (!$sendAll) {
            $q->where(function($w){
                $w->whereNull('a.status')            // belum ada baris absensi
                ->orWhere('a.status', '!=', 'hadir'); // ada tapi bukan hadir
            });
        }

        // Ambil daftar unik per user (jaga-jaga)
        $targets = $q->get()->unique('id')->values();

        if ($targets->isEmpty()) {
            return back()->with('success', 'Tidak ada penerima yang memenuhi kriteria.');
        }

        // Rakit pesan
        \Carbon\Carbon::setLocale('id');
        $tgl  = \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y');
        $jam  = $rapat->waktu_mulai;
        $tempat = $rapat->tempat;
        $sender = env('FONNTE_SENDER', 'Sistem Rapat');

        $sent = 0; $skipped = 0;
        foreach ($targets as $t) {
            // Normalisasi nomor HP
            $msisdn = $this->normalizeMsisdn($t->no_hp ?? null);
            if (!$msisdn) { $skipped++; continue; }

            // Pesan formal
            $msg =
                "Assalamu’alaikum Wr. Wb.\n\n".
                "*{$sender}*\n\n".
                "Yth. *{$t->name}*,\n".
                "Rapat berikut telah dimulai. Mohon kesediaannya untuk melakukan *absensi* melalui tautan di bawah ini:\n\n".
                "📄 *{$rapat->judul}*\n".
                "🗓️ {$tgl}\n".
                "⏰ {$jam} WIT\n".
                "📍 {$tempat}\n\n".
                "Tautan absensi:\n{$absensiUrl}\n\n".
                "Terima kasih.\n".
                "Wassalamu’alaikum Wr. Wb.";

            try {
                if ($this->sendWaFonnte($msisdn, $msg)) $sent++; else $skipped++;
            } catch (\Throwable $e) {
                $skipped++;
            }
        }

        $note = $sendAll
            ? "Kirim WA (SEMUA peserta)"
            : "Kirim WA (yang BELUM absen)";
        return back()->with('success', "{$note}: terkirim {$sent}, dilewati {$skipped}.");
    }

}
