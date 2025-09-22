<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AbsensiController extends Controller
{
    /**
     * Admin: Daftar rapat untuk pengelolaan absensi
     * - Filter: kategori, tanggal, keyword (judul/nomor)
     * - Paginasi: 6/baris
     */
    public function index(Request $request)
    {
        $perPage = 6;

        // Ambil data pilihan kategori untuk filter
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        // Query utama rapat + kategori + pembuat
        $q = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->leftJoin('users as pembuat', 'rapat.dibuat_oleh', '=', 'pembuat.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'pembuat.name as nama_pembuat'
            );

        // === FILTERS ===
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

        // Ambil data dengan paginasi
        $daftar_rapat = $q->orderBy('rapat.tanggal', 'desc')
            ->orderBy('rapat.waktu_mulai', 'desc')
            ->paginate($perPage)
            ->appends($request->query());

        // Hitung jumlah peserta tiap rapat (sekali query, hemat N+1)
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

        // Kirim juga nilai filter agar form tetap terisi
        $filter = [
            'kategori' => $request->kategori,
            'tanggal'  => $request->tanggal,
            'keyword'  => $request->keyword,
        ];

        return view('absensi.index', compact('daftar_rapat', 'daftar_kategori', 'filter'));
    }

    // Admin: Form tambah absensi
    public function create()
    {
        $peserta = DB::table('users')->where('role', 'peserta')->orderBy('name')->get();
        $rapat   = DB::table('rapat')->orderBy('tanggal', 'desc')->orderBy('waktu_mulai', 'desc')->get();
        return view('absensi.create', compact('peserta', 'rapat'));
    }

    // Admin: Simpan absensi
    public function store(Request $request)
    {
        $request->validate([
            'id_rapat' => 'required|exists:rapat,id',
            'id_user'  => 'required|exists:users,id',
            'status'   => 'required|in:hadir,izin,alfa',
        ]);

        // Cegah absensi ganda
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

    // Admin: Form edit absensi
    public function edit($id)
    {
        $absensi = DB::table('absensi')->where('id', $id)->first();
        $peserta = DB::table('users')->where('role', 'peserta')->orderBy('name')->get();
        $rapat   = DB::table('rapat')->orderBy('tanggal', 'desc')->orderBy('waktu_mulai', 'desc')->get();
        return view('absensi.edit', compact('absensi', 'peserta', 'rapat'));
    }

    // Admin: Update absensi
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

    // Admin: Hapus absensi
    public function destroy($id)
    {
        DB::table('absensi')->where('id', $id)->delete();
        return redirect()->route('absensi.index')->with('success', 'Absensi berhasil dihapus.');
    }

    // Peserta: Lihat absensi milik sendiri
    public function absensiSaya()
    {
        $absensi = DB::table('absensi')
            ->join('rapat', 'absensi.id_rapat', '=', 'rapat.id')
            ->where('absensi.id_user', Auth::id())
            ->select('absensi.*', 'rapat.judul', 'rapat.tanggal', 'rapat.tempat')
            ->orderBy('rapat.tanggal', 'desc')
            ->orderBy('rapat.waktu_mulai', 'desc')
            ->get();

        // Undangan yang belum diisi absensi
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

    // Peserta: Halaman scan QR (cek undangan & status)
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

    // Peserta: Simpan hasil scan QR (upsert hadir)
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
     * Helper:
     * Jika semua approval dokumen UNDANGAN sudah approved,
     * otomatis buat QR untuk dokumen ABSENSI (unik & beda dari undangan).
     * - Disimpan sebagai row di approval_requests (doc_type='absensi'), approver = approval1_user_id.
     * - Jika sudah ada & punya QR, tidak dibuat ulang.
     * - QR berisi URL verifikasi (route qr.verify) agar saat scan langsung ke halaman verifikasi.
     */
    public function ensureAbsensiQrMirrorsUndangan(int $rapatId): void
    {
        $rapat = DB::table('rapat')->where('id', $rapatId)->first();
        if (!$rapat || !$rapat->approval1_user_id) return;

        // 1) Pastikan semua step UNDANGAN sudah approved
        $steps = DB::table('approval_requests')
            ->where('rapat_id', $rapatId)
            ->where('doc_type', 'undangan')
            ->orderBy('order_index')
            ->get();

        if ($steps->isEmpty()) return;

        $allApproved = $steps->every(function($s){ return $s->status === 'approved'; });
        if (!$allApproved) return;

        // 2) Apakah sudah ada QR ABSENSI?
        $absensiRow = DB::table('approval_requests')
            ->where('rapat_id', $rapatId)
            ->where('doc_type', 'absensi')
            ->where('approver_user_id', $rapat->approval1_user_id)
            ->first();

        if ($absensiRow && $absensiRow->status === 'approved' && $absensiRow->signature_qr_path) {
            return; // sudah ada QR absensi -> selesai
        }

        // 3) Siapkan payload unik utk ABSENSI (beda dari undangan):
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

        // HMAC
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

        // === QR berisi URL verifikasi (bukan payload mentah) ===
        $qrContent = route('qr.verify', ['d' => base64_encode($payloadJson)]);
        $encoded   = urlencode($qrContent);
        $qrUrl     = "https://chart.googleapis.com/chart?chs=220x220&cht=qr&chl={$encoded}";

        // 4) Simpan PNG ke public/qr
        $qrDir = public_path('qr');
        if (!is_dir($qrDir)) @mkdir($qrDir, 0755, true);

        $filename     = 'qr_absensi_r'.$rapat->id.'_a'.($approver->id ?? '0').'_'.Str::random(6).'.png';
        $relativePath = 'qr/'.$filename;
        $absolutePath = public_path($relativePath);

        $pngData = @file_get_contents($qrUrl);
        if ($pngData === false) {
            $alt = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={$encoded}";
            $pngData = @file_get_contents($alt);
        }
        if ($pngData === false) {
            // gagal membuat QR? jangan blok proses
            return;
        }
        file_put_contents($absolutePath, $pngData);

        // 5) Upsert row ABSENSI jadi approved + simpan QR
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
                'order_index'       => 1,            // tunggal/final
                'status'            => 'approved',   // otomatis approved karena mengikuti undangan
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
     * - Membuat QR ABSENSI otomatis jika seluruh approval UNDANGAN sudah approved
     * - Menyisipkan QR ABSENSI (bukan QR undangan) ke PDF
     * - Menyertakan NAMA & JABATAN Approval Final (approval1_user_id)
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

    // Daftar peserta + status absensi
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

    // Ambil QR ABSENSI yang dibuat otomatis
    $absensiReq = DB::table('approval_requests')
        ->where('rapat_id', $id_rapat)
        ->where('doc_type', 'absensi')
        ->where('approver_user_id', $rapat->approval1_user_id)
        ->first();

    // Siapkan data untuk ditampilkan di view
    $absensi_qr_data = null; // data URI base64 (disarankan)
    $absensi_qr_web  = null; // url publik (opsional)
    $absensi_qr_fs   = null; // full path filesystem (opsional)

    if ($absensiReq && $absensiReq->signature_qr_path) {
        $absensi_qr_fs = public_path($absensiReq->signature_qr_path);
        if (is_file($absensi_qr_fs)) {
            // Embed sebagai base64 agar DomPDF pasti bisa render
            $absensi_qr_data = 'data:image/png;base64,' . base64_encode(file_get_contents($absensi_qr_fs));
            // Tambahan opsional kalau view Anda masih butuh
            $absensi_qr_web  = url($absensiReq->signature_qr_path);
        }
    }

    // Approver final (approval1_user_id)
    $approverFinal = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
    $approver_final_nama    = $approverFinal->name ?? null;
    $approver_final_jabatan = $approverFinal->jabatan ?? 'Penanggung Jawab';

    $pdf = Pdf::loadView('absensi.laporan_pdf', [
        'rapat'                  => $rapat,
        'peserta'                => $peserta,
        // gunakan ini di <img src="{{ $absensi_qr_data }}"> pada view
        'absensi_qr_data'        => $absensi_qr_data,
        // ini opsional/kompatibilitas
        'absensi_qr_web'         => $absensi_qr_web,
        'absensi_qr_fs'          => $absensi_qr_fs,
        'absensi_req'            => $absensiReq,

        'approver_final_nama'    => $approver_final_nama,
        'approver_final_jabatan' => $approver_final_jabatan,

        // kop surat untuk PDF
        'kop'                    => public_path('kop_absen.jpg'),
    ])->setPaper('A4', 'portrait');

    $filename = 'Laporan-Absensi-' . str_replace(' ', '-', $rapat->judul) . '.pdf';
    return $pdf->download($filename);
}
}
