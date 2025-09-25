<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AbsensiController extends Controller
{
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

    return view('absensi.scan', compact('rapat','sudah_absen'));
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

    // === NOTIFIKASI WA ===
    $this->notifyAbsensiWa(Auth::id(), $rapat, 'hadir');

    return redirect()->route('absensi.scan', $token)->with('success', 'Absensi (TTD) berhasil direkam. Terima kasih!');
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
        $qrUrl     = "https://chart.googleapis.com/chart?chs=600x600&cht=qr&chl={$encoded}"; // besar dulu, nanti dompdf kecilkan

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
        $logoPath = public_path('logo_qr.png'); // ganti sesuai lokasi logo Anda
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

                        // logo max 20% lebar QR
                        $targetW = (int) round($qrW * 0.20);
                        $targetH = (int) round($logoH * ($targetW / $logoW));

                        $dstX = (int) round(($qrW - $targetW) / 2);
                        $dstY = (int) round(($qrH - $targetH) / 2);

                        // resize dg alpha
                        $logoResized = imagecreatetruecolor($targetW, $targetH);
                        imagealphablending($logoResized, false);
                        imagesavealpha($logoResized, true);
                        imagecopyresampled($logoResized, $logoImg, 0, 0, 0, 0, $targetW, $targetH, $logoW, $logoH);

                        // tempel
                        imagecopy($qrImg, $logoResized, $dstX, $dstY, 0, 0, $targetW, $targetH);

                        // simpan hasil akhir
                        imagepng($qrImg, $absolutePath);
                        imagedestroy($logoResized);
                        imagedestroy($logoImg);
                        imagedestroy($qrImg);
                        $saved = true;
                    }
                }
                if (!$saved) {
                    // simpan apa adanya (kalau logo tidak ada)
                    imagepng($qrImg, $absolutePath);
                    imagedestroy($qrImg);
                    $saved = true;
                }
            }
        }

        if (!$saved) {
            // fallback tulis file mentah
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
public function exportPdf($id_rapat)
{
    // Pastikan QR absensi sudah ada & up-to-date
    $this->ensureAbsensiQrMirrorsUndangan((int) $id_rapat);

    // Data rapat + kategori
    $rapat = DB::table('rapat')
        ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
        ->select('rapat.*', 'kategori_rapat.nama as nama_kategori')
        ->where('rapat.id', $id_rapat)
        ->first();

    if (!$rapat) abort(404);

    // Daftar peserta + status absensi + TTD  // <<< add ttd_path & ttd_hash
    $peserta = DB::table('undangan')
        ->join('users', 'undangan.id_user', '=', 'users.id')
        ->leftJoin('absensi', function ($q) use ($id_rapat) {
            $q->on('absensi.id_user', '=', 'undangan.id_user')
              ->where('absensi.id_rapat', '=', $id_rapat);
        })
        ->where('undangan.id_rapat', $id_rapat)
        ->select(
            'users.id as user_id',
            'users.name',
            'users.jabatan',
            'users.unit',
            'absensi.status',
            'absensi.waktu_absen',
            'absensi.ttd_path',     // <<< add
            'absensi.ttd_hash'      // <<< optional, buat audit
        )
        ->orderBy('users.name')
        ->get();

    // Siapkan base64 untuk tiap TTD agar DomPDF stabil
    foreach ($peserta as $p) {
        $p->ttd_data = null; // data URI base64 PNG
        if (!empty($p->ttd_path)) {
            $fs = public_path($p->ttd_path);
            if (is_file($fs)) {
                $p->ttd_data = 'data:image/png;base64,' . base64_encode(@file_get_contents($fs));
            }
        }
    }

    // Ambil QR ABSENSI (logo di tengah sudah kamu pasang sebelumnya)
    $absensiReq = DB::table('approval_requests')
        ->where('rapat_id', $id_rapat)
        ->where('doc_type', 'absensi')
        ->where('approver_user_id', $rapat->approval1_user_id)
        ->first();

    $absensi_qr_data = null; $absensi_qr_web = null; $absensi_qr_fs = null;
    if ($absensiReq && $absensiReq->signature_qr_path) {
        $absensi_qr_fs = public_path($absensiReq->signature_qr_path);
        if (is_file($absensi_qr_fs)) {
            $absensi_qr_data = 'data:image/png;base64,' . base64_encode(file_get_contents($absensi_qr_fs));
            $absensi_qr_web  = url($absensiReq->signature_qr_path);
        }
    }

    // Approver final (approval1_user_id)
    $approverFinal = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
    $approver_final_nama    = $approverFinal->name ?? null;
    $approver_final_jabatan = $approverFinal->jabatan ?? 'Penanggung Jawab';

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('absensi.laporan_pdf', [
        'rapat'                  => $rapat,
        'peserta'                => $peserta,          // <<< sudah include ttd_data per baris
        'absensi_qr_data'        => $absensi_qr_data,
        'absensi_qr_web'         => $absensi_qr_web,
        'absensi_qr_fs'          => $absensi_qr_fs,
        'absensi_req'            => $absensiReq,
        'approver_final_nama'    => $approver_final_nama,
        'approver_final_jabatan' => $approver_final_jabatan,
        'kop'                    => public_path('kop_absen.jpg'),
    ])->setPaper('A4', 'portrait');

    $filename = 'Laporan-Absensi-' . str_replace(' ', '-', $rapat->judul) . '.pdf';
    return $pdf->download($filename);
}

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

// ——— Rakit & kirim pesan absensi
private function notifyAbsensiWa(int $userId, \stdClass $rapat, string $status): void
{
    $user = DB::table('users')->where('id', $userId)->select('name','no_hp')->first();
    if (!$user) return;

    $msisdn = $this->normalizeMsisdn($user->no_hp ?? null);
    if (!$msisdn) return;

    \Carbon\Carbon::setLocale('id');
    $tgl = \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y');
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
        // log error jika mau
    }
}

}
