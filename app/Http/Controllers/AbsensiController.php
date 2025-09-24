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

        return view('absensi.scan', compact('rapat', 'sudah_absen'));
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

        $ada = DB::table('absensi')
            ->where('id_rapat', $rapat->id)
            ->where('id_user', Auth::id())
            ->exists();

        if ($ada) {
            DB::table('absensi')
                ->where('id_rapat', $rapat->id)
                ->where('id_user', Auth::id())
                ->update([
                    'status'      => 'hadir',
                    'waktu_absen' => now(),
                    'updated_at'  => now(),
                ]);
        } else {
            DB::table('absensi')->insert([
                'id_rapat'    => $rapat->id,
                'id_user'     => Auth::id(),
                'status'      => 'hadir',
                'waktu_absen' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        return redirect()->route('absensi.scan', $token)->with('success', 'Absensi berhasil direkam. Terima kasih!');
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
        $this->ensureAbsensiQrMirrorsUndangan((int) $id_rapat);

        $rapat = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select('rapat.*', 'kategori_rapat.nama as nama_kategori')
            ->where('rapat.id', $id_rapat)
            ->first();

        if (!$rapat) abort(404);

        $peserta = DB::table('undangan')
            ->join('users', 'undangan.id_user', '=', 'users.id')
            ->leftJoin('absensi', function ($q) use ($id_rapat) {
                $q->on('absensi.id_user', '=', 'undangan.id_user')
                  ->where('absensi.id_rapat', '=', $id_rapat);
            })
            ->where('undangan.id_rapat', $id_rapat)
            ->select('users.name', 'users.jabatan', 'users.unit', 'absensi.status', 'absensi.waktu_absen')
            ->orderBy('users.name')
            ->get();

        $absensiReq = DB::table('approval_requests')
            ->where('rapat_id', $id_rapat)
            ->where('doc_type', 'absensi')
            ->where('approver_user_id', $rapat->approval1_user_id)
            ->first();

        $absensi_qr_data = null;
        $absensi_qr_web  = null;
        $absensi_qr_fs   = null;

        if ($absensiReq && $absensiReq->signature_qr_path) {
            $absensi_qr_fs = public_path($absensiReq->signature_qr_path);
            if (is_file($absensi_qr_fs)) {
                $absensi_qr_data = 'data:image/png;base64,' . base64_encode(file_get_contents($absensi_qr_fs));
                $absensi_qr_web  = url($absensiReq->signature_qr_path);
            }
        }

        $approverFinal = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
        $approver_final_nama    = $approverFinal->name ?? null;
        $approver_final_jabatan = $approverFinal->jabatan ?? 'Penanggung Jawab';

        $pdf = Pdf::loadView('absensi.laporan_pdf', [
            'rapat'                  => $rapat,
            'peserta'                => $peserta,
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
}
