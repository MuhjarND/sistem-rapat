<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AbsensiController extends Controller
{
    /**
     * Admin: Daftar rapat untuk pengelolaan absensi
     * - Filter: kategori, tanggal, keyword (judul/nomor)
     * - Paginasi: 10/baris
     */
    public function index(Request $request)
    {
        $perPage = 6;

        // Ambil data pilihan kategori untuk filter
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        // Query utama rapat + kategori + pimpinan + pembuat
        $q = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
            ->leftJoin('users as pembuat', 'rapat.dibuat_oleh', '=', 'pembuat.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
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
            ->appends($request->query()); // agar filter tetap ikut saat pindah halaman

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

    // Export PDF Laporan Absensi untuk 1 rapat
    public function exportPdf($id_rapat)
    {
        $rapat = DB::table('rapat')
            ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select(
                'rapat.*',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            )
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
            ->select('users.name', 'users.jabatan', 'absensi.status', 'absensi.waktu_absen')
            ->orderBy('users.name')
            ->get();

        $pdf = Pdf::loadView('absensi.laporan_pdf', [
            'rapat'   => $rapat,
            'peserta' => $peserta,
            'kop'     => public_path('kop_absen.jpg'),
        ])->setPaper('A4', 'portrait');

        $filename = 'Laporan-Absensi-' . str_replace(' ', '-', $rapat->judul) . '.pdf';
        return $pdf->download($filename);
    }
}
