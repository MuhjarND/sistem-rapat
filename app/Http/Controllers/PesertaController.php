<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route; // cek nama route
use Carbon\Carbon;

class PesertaController extends Controller
{
    /**
     * Dashboard Peserta
     */
    public function dashboard()
    {
        $userId = Auth::id();
        $today  = now()->toDateString();
        $nowTime = now()->format('H:i:s'); // <<< tambah: waktu saat ini (HH:MM:SS)
        $limit  = 8;

        // ===== Stats
        $stats = [
            'total_diundang' => DB::table('undangan')->where('id_user', $userId)->count(),

            'upcoming_count' => DB::table('undangan')
                ->join('rapat','undangan.id_rapat','=','rapat.id')
                ->where('undangan.id_user',$userId)
                ->whereDate('rapat.tanggal','>=',$today)
                ->count(),

            'hadir' => DB::table('absensi')
                ->where('id_user',$userId)
                ->where('status','hadir')
                ->count(),

            'izin'  => DB::table('absensi')
                ->where('id_user',$userId)
                ->where('status','izin')
                ->count(),

            'notulensi_tersedia' => DB::table('undangan')
                ->join('notulensi','notulensi.id_rapat','=','undangan.id_rapat')
                ->where('undangan.id_user',$userId)
                ->count(),
        ];

        // ===== Rapat terdekat (>= SEKARANG)
        // - Besok dst: ambil semua
        // - Hari ini: hanya yang jam mulai >= sekarang
        $rapat_terdekat = DB::table('undangan')
            ->join('rapat','undangan.id_rapat','=','rapat.id')
            ->where('undangan.id_user', $userId)
            ->where(function($q) use ($today, $nowTime){
                $q->whereDate('rapat.tanggal','>', $today)
                  ->orWhere(function($qq) use ($today, $nowTime){
                      $qq->whereDate('rapat.tanggal', $today)
                         ->where('rapat.waktu_mulai', '>=', $nowTime);
                  });
            })
            ->select('rapat.*','rapat.token_qr')
            ->orderBy('rapat.tanggal')
            ->orderBy('rapat.waktu_mulai')
            ->first();

        // ===== Absensi perlu konfirmasi (rapat s/d hari ini & belum absen)
        $absensi_pending = DB::table('undangan')
            ->join('rapat','undangan.id_rapat','=','rapat.id')
            ->leftJoin('absensi', function($q) use ($userId){
                $q->on('absensi.id_rapat','=','rapat.id')
                  ->where('absensi.id_user','=',$userId);
            })
            ->where('undangan.id_user',$userId)
            ->whereDate('rapat.tanggal','<=',$today)
            ->whereNull('absensi.id')
            ->select('rapat.*','rapat.token_qr')
            ->orderBy('rapat.tanggal')
            ->orderBy('rapat.waktu_mulai')
            ->limit(10)
            ->get();

        // ===== Rapat akan datang (7 hari ke depan termasuk hari ini)
        $end7 = now()->addDays(7)->toDateString();
        $rapat_akan_datang = DB::table('undangan')
            ->join('rapat','undangan.id_rapat','=','rapat.id')
            ->where('undangan.id_user',$userId)
            ->whereBetween(DB::raw('DATE(rapat.tanggal)'), [$today, $end7])
            ->select('rapat.*','rapat.token_qr')
            ->orderBy('rapat.tanggal')
            ->orderBy('rapat.waktu_mulai')
            ->get();

        // ===== Riwayat rapat terbaru
        $riwayat_rapat = DB::table('undangan')
            ->join('rapat','undangan.id_rapat','=','rapat.id')
            ->leftJoin('absensi', function($q) use ($userId){
                $q->on('absensi.id_rapat','=','rapat.id')
                  ->where('absensi.id_user','=',$userId);
            })
            ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
            ->where('undangan.id_user',$userId)
            ->select(
                'rapat.*',
                'rapat.token_qr',
                'absensi.status as absensi_status',
                DB::raw('CASE WHEN notulensi.id IS NULL THEN 0 ELSE 1 END AS ada_notulensi'),
                'notulensi.id as notulensi_id'
            )
            ->orderBy('rapat.tanggal','desc')
            ->orderBy('rapat.waktu_mulai','desc')
            ->limit($limit)
            ->get();

        return view('peserta.dashboard', compact(
            'stats',
            'rapat_terdekat',
            'absensi_pending',
            'rapat_akan_datang',
            'riwayat_rapat'
        ));
    }

    /**
     * Halaman daftar rapat milik peserta.
     */
    public function rapat(Request $request)
    {
        $userId = Auth::id();
        $today  = Carbon::today()->toDateString();

        $jenis   = $request->get('jenis', 'all'); // upcoming | past | all
        $keyword = trim((string)$request->get('q',''));
        $from    = $request->get('from');
        $to      = $request->get('to');

        $q = DB::table('undangan')
            ->join('rapat','undangan.id_rapat','=','rapat.id')
            ->leftJoin('absensi', function($qq) use ($userId){
                $qq->on('absensi.id_rapat','=','rapat.id')
                   ->where('absensi.id_user','=',$userId);
            })
            ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
            ->leftJoin('kategori_rapat','rapat.id_kategori','=','kategori_rapat.id')
            ->where('undangan.id_user',$userId)
            ->select(
                'rapat.id',
                'rapat.judul',
                'rapat.nomor_undangan',
                'rapat.tanggal',
                'rapat.waktu_mulai',
                'rapat.tempat',
                'rapat.token_qr',
                'kategori_rapat.nama as nama_kategori',
                'absensi.status as status_absensi',
                'notulensi.id as id_notulensi'
            );

        if ($jenis === 'upcoming') {
            $q->whereDate('rapat.tanggal','>=',$today);
        } elseif ($jenis === 'past') {
            $q->whereDate('rapat.tanggal','<',$today);
        }

        if ($keyword !== '') {
            $q->where(function($qq) use ($keyword){
                $qq->where('rapat.judul','like',"%{$keyword}%")
                   ->orWhere('rapat.nomor_undangan','like',"%{$keyword}%")
                   ->orWhere('rapat.tempat','like',"%{$keyword}%");
            });
        }

        if (!empty($from)) $q->whereDate('rapat.tanggal','>=',$from);
        if (!empty($to))   $q->whereDate('rapat.tanggal','<=',$to);

        if ($jenis === 'upcoming') {
            $q->orderBy('rapat.tanggal')->orderBy('rapat.waktu_mulai');
        } else {
            $q->orderBy('rapat.tanggal','desc')->orderBy('rapat.waktu_mulai','desc');
        }

        $rapat = $q->paginate(6)->appends($request->query());

        return view('peserta.rapat', [
            'rapat'  => $rapat,
            'filter' => [
                'jenis' => $jenis,
                'q'     => $keyword,
                'from'  => $from,
                'to'    => $to,
            ],
        ]);
    }

    /** Detail rapat + URL preview undangan PDF */
    public function showRapat($id)
    {
        $rapat = DB::table('rapat')
            ->leftJoin('kategori_rapat','rapat.id_kategori','=','kategori_rapat.id')
            ->leftJoin('pimpinan_rapat','rapat.id_pimpinan','=','pimpinan_rapat.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan'
            )
            ->where('rapat.id',$id)
            ->first();

        if (!$rapat) abort(404);

        $diundang = DB::table('undangan')
            ->where('id_rapat',$id)
            ->where('id_user',Auth::id())
            ->exists();
        if (!$diundang) abort(403, 'Anda tidak terdaftar pada rapat ini.');

        $penerima = DB::table('undangan')
            ->join('users','undangan.id_user','=','users.id')
            ->where('undangan.id_rapat',$id)
            ->select('users.name','users.jabatan','users.unit')
            ->orderBy('users.name')
            ->get();

        $notulensi = DB::table('notulensi')->where('id_rapat',$id)->first();
        $notulensi_id = $notulensi->id ?? null;

        // URL PDF undangan untuk inline preview (controller undanganPdf)
        $undangan_pdf_url = null;
        if (Route::has('rapat.undangan.pdf')) {
            $undangan_pdf_url = route('rapat.undangan.pdf', $id);
        } elseif (Route::has('undangan.pdf')) {
            $undangan_pdf_url = route('undangan.pdf', $id);
        } elseif (Route::has('undangan.show.pdf')) {
            $undangan_pdf_url = route('undangan.show.pdf', $id);
        }

        return view('peserta.rapat.show', compact(
            'rapat','penerima','notulensi_id','undangan_pdf_url'
        ));
    }

    /** Form konfirmasi / isi absensi (GET) — fallback bila token_qr kosong */
    public function absensi($id)
    {
        $rapat = DB::table('rapat')->where('id',$id)->first();
        if (!$rapat) abort(404);

        $diundang = DB::table('undangan')
            ->where('id_rapat',$id)
            ->where('id_user',Auth::id())
            ->exists();
        if (!$diundang) abort(403, 'Anda tidak terdaftar pada rapat ini.');

        $absensi = DB::table('absensi')
            ->where('id_rapat',$id)
            ->where('id_user',Auth::id())
            ->first();

        return view('peserta.absensi.form', compact('rapat','absensi'));
    }

    /** Lihat notulensi (show) */
    public function showNotulensi($idRapat)
    {
        $diundang = DB::table('undangan')
            ->where('id_rapat',$idRapat)
            ->where('id_user',Auth::id())
            ->exists();
        if (!$diundang) abort(403);

        $notulensi = DB::table('notulensi')->where('id_rapat',$idRapat)->first();
        if (!$notulensi) abort(404, 'Notulensi belum tersedia.');

        $rapat = DB::table('rapat')->where('id',$idRapat)->first();
        $detail = DB::table('notulensi_detail')
            ->where('id_notulensi',$notulensi->id)
            ->orderBy('urut')
            ->get();

        return view('peserta.notulensi.show', compact('rapat','notulensi','detail'));
    }
}
