<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

        public function index()
    {
        // ====== Statistik Utama ======
        $total_rapat      = DB::table('rapat')->count();
        $rapat_bulan_ini  = DB::table('rapat')
            ->whereMonth('tanggal', date('m'))
            ->whereYear('tanggal', date('Y'))
            ->count();

        // Notulensi: sudah & belum
        $notulensi_sudah = DB::table('notulensi')->distinct('id_rapat')->count('id_rapat');
        $notulensi_belum = max(0, $total_rapat - $notulensi_sudah);

        // Total laporan (dari modul upload laporan)
        $total_laporan = DB::table('laporan_files')->count();

        // ====== Grafik: Rapat per Bulan (tahun berjalan) ======
        $rapat_per_bulan_raw = DB::table('rapat')
            ->select(DB::raw('MONTH(tanggal) as bln'), DB::raw('COUNT(*) as jml'))
            ->whereYear('tanggal', date('Y'))
            ->groupBy(DB::raw('MONTH(tanggal)'))
            ->orderBy(DB::raw('MONTH(tanggal)'))
            ->get();

        $label_bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $data_bulan  = array_fill(0, 12, 0);
        foreach ($rapat_per_bulan_raw as $r) {
            $idx = (int)$r->bln - 1;
            if ($idx >= 0 && $idx < 12) $data_bulan[$idx] = (int)$r->jml;
        }

        // ====== Grafik: Top Kategori Rapat ======
        $kategori_raw = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'kategori_rapat.id', '=', 'rapat.id_kategori')
            ->select('kategori_rapat.nama as kategori', DB::raw('COUNT(rapat.id) as jml'))
            ->groupBy('kategori_rapat.nama')
            ->orderBy('jml', 'desc')
            ->limit(8)
            ->get();

        $label_kategori = $kategori_raw->pluck('kategori')->map(function($v){ return $v ?: 'Tanpa Kategori'; })->values();
        $data_kategori  = $kategori_raw->pluck('jml')->map(function($v){ return (int)$v; })->values();

        // ====== Daftar Dinamis ======
        $rapat_akan_datang = DB::table('rapat')
            ->whereDate('tanggal', '>=', date('Y-m-d'))
            ->whereDate('tanggal', '<=', date('Y-m-d', strtotime('+7 days')))
            ->orderBy('tanggal')
            ->orderBy('waktu_mulai')
            ->limit(5)
            ->get(['id','judul','tanggal','waktu_mulai','tempat']);

        $rapat_terbaru = DB::table('rapat')
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_mulai', 'desc')
            ->limit(5)
            ->get(['id','judul','tanggal','waktu_mulai','tempat']);

        $laporan_terbaru = DB::table('laporan_files')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id','judul','file_name','created_at']);

        return view('dashboard.index', [
            'total_rapat'      => $total_rapat,
            'rapat_bulan_ini'  => $rapat_bulan_ini,
            'notulensi_sudah'  => $notulensi_sudah,
            'notulensi_belum'  => $notulensi_belum,
            'total_laporan'    => $total_laporan,

            'label_bulan'      => $label_bulan,
            'data_bulan'       => $data_bulan,

            'label_kategori'   => $label_kategori,
            'data_kategori'    => $data_kategori,

            'rapat_akan_datang'=> $rapat_akan_datang,
            'rapat_terbaru'    => $rapat_terbaru,
            'laporan_terbaru'  => $laporan_terbaru,
        ]);
    }
    // Dashboard Admin
    public function admin()
    {
        $jumlah_rapat = DB::table('rapat')->count();
        $jumlah_undangan = DB::table('undangan')->count();
        $jumlah_absensi = DB::table('absensi')->count();
        $jumlah_notulensi = DB::table('notulensi')->count();
        $user_count = DB::table('users')->count();
        $rapat_terbaru = DB::table('rapat')->orderBy('tanggal', 'desc')->limit(5)->get();

        return view('dashboard.admin', compact('jumlah_rapat', 'jumlah_undangan', 'jumlah_absensi', 'jumlah_notulensi', 'user_count', 'rapat_terbaru'));
    }

    // Dashboard Notulis
    public function notulis()
    {
        $jumlah_notulensi = DB::table('notulensi')->where('id_user', Auth::id())->count();
        $notulensi_terbaru = DB::table('notulensi')
            ->join('rapat', 'notulensi.id_rapat', '=', 'rapat.id')
            ->where('notulensi.id_user', Auth::id())
            ->orderBy('notulensi.created_at', 'desc')
            ->limit(5)
            ->select('notulensi.*', 'rapat.judul as judul_rapat', 'rapat.tanggal')
            ->get();

        $rapat_belum_dinotulen = DB::table('rapat')
            ->whereNotIn('id', function ($q) {
                $q->select('id_rapat')->from('notulensi')->where('id_user', Auth::id());
            })
            ->where('tanggal', '<=', date('Y-m-d'))
            ->orderBy('tanggal', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.notulis', compact('jumlah_notulensi', 'notulensi_terbaru', 'rapat_belum_dinotulen'));
    }

    // Dashboard Peserta
    public function peserta()
    {
        $jumlah_undangan = DB::table('undangan')->where('id_user', Auth::id())->count();
        $jumlah_hadir = DB::table('absensi')->where('id_user', Auth::id())->where('status', 'hadir')->count();
        $jumlah_izin = DB::table('absensi')->where('id_user', Auth::id())->where('status', 'izin')->count();
        $jumlah_alfa = DB::table('absensi')->where('id_user', Auth::id())->where('status', 'alfa')->count();

        $undangan_terbaru = DB::table('undangan')
            ->join('rapat', 'undangan.id_rapat', '=', 'rapat.id')
            ->where('undangan.id_user', Auth::id())
            ->orderBy('rapat.tanggal', 'desc')
            ->limit(5)
            ->select('undangan.*', 'rapat.judul', 'rapat.tanggal')
            ->get();

        return view('dashboard.peserta', compact('jumlah_undangan', 'jumlah_hadir', 'jumlah_izin', 'jumlah_alfa', 'undangan_terbaru'));
    }
}
