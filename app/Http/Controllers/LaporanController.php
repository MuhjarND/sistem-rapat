<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use iio\libmergepdf\Merger;

class LaporanController extends Controller
{
    /** =========================
     *  LIST + FILTER + UPLOADS
     *  ========================= */
    public function index(Request $request)
    {
        // filter
        $dari     = $request->get('dari');      // yyyy-mm-dd
        $sampai   = $request->get('sampai');    // yyyy-mm-dd
        $id_kat   = $request->get('id_kategori');
        $status_n = $request->get('status_notulensi'); // 'sudah' | 'belum' | null

        // rekap per rapat
        $q = DB::table('rapat')
            ->leftJoin('kategori_rapat','kategori_rapat.id','=','rapat.id_kategori')
            ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
            ->leftJoin('undangan', function($j){ $j->on('undangan.id_rapat','=','rapat.id'); })
            ->leftJoin('absensi',  function($j){ $j->on('absensi.id_rapat','=','rapat.id'); })
            ->select(
                'rapat.id','rapat.judul','rapat.tanggal','rapat.waktu_mulai','rapat.tempat',
                'kategori_rapat.nama as nama_kategori',
                DB::raw('COUNT(DISTINCT undangan.id) as jml_diundang'),
                DB::raw("SUM(CASE WHEN absensi.status='hadir' THEN 1 ELSE 0 END) as jml_hadir"),
                DB::raw("SUM(CASE WHEN absensi.status='tidak_hadir' THEN 1 ELSE 0 END) as jml_tidak_hadir"),
                DB::raw("SUM(CASE WHEN absensi.status='izin' THEN 1 ELSE 0 END) as jml_izin"),
                DB::raw('CASE WHEN MIN(notulensi.id) IS NULL THEN 0 ELSE 1 END as ada_notulensi')
            )
            ->when($dari,   fn($qq)=>$qq->whereDate('rapat.tanggal','>=',$dari))
            ->when($sampai, fn($qq)=>$qq->whereDate('rapat.tanggal','<=',$sampai))
            ->when($id_kat, fn($qq)=>$qq->where('rapat.id_kategori',$id_kat))
            ->groupBy('rapat.id','rapat.judul','rapat.tanggal','rapat.waktu_mulai','rapat.tempat','kategori_rapat.nama');

        if ($status_n === 'sudah')      $q->having('ada_notulensi','=',1);
        elseif ($status_n === 'belum')  $q->having('ada_notulensi','=',0);

        $data = $q->orderBy('rapat.tanggal','desc')->get();

        $kategori = DB::table('kategori_rapat')->select('id','nama')->orderBy('nama')->get();

        // daftar upload file laporan (JOIN kategori & ikut tersaring)
        $uploads = DB::table('laporan_files')
            ->leftJoin('rapat','rapat.id','=','laporan_files.id_rapat')
            ->leftJoin('kategori_rapat','kategori_rapat.id','=','laporan_files.id_kategori')
            ->select(
                'laporan_files.*',
                'rapat.judul as judul_rapat',
                'kategori_rapat.nama as nama_kategori'
            )
            ->when($dari,   fn($q2)=>$q2->whereDate('laporan_files.tanggal_laporan','>=',$dari))
            ->when($sampai, fn($q2)=>$q2->whereDate('laporan_files.tanggal_laporan','<=',$sampai))
            ->when($id_kat, fn($q2)=>$q2->where('laporan_files.id_kategori',$id_kat))
            ->orderBy('laporan_files.created_at','desc')
            ->get();

        // list rapat (kalau suatu saat ingin dipakai lagi)
        $rapatList = DB::table('rapat')->select('id','judul','tanggal')->orderBy('tanggal','desc')->get();

        return view('laporan.index', [
            'data'      => $data,
            'kategori'  => $kategori,
            'filter'    => compact('dari','sampai','id_kat','status_n'),
            'uploads'   => $uploads,
            'rapatList' => $rapatList,
        ]);
    }

    /** =============
     *  CETAK REKAP
     *  ============= */
    public function cetak(Request $request)
    {
        $dari     = $request->get('dari');
        $sampai   = $request->get('sampai');
        $id_kat   = $request->get('id_kategori');
        $status_n = $request->get('status_notulensi');

        $q = DB::table('rapat')
            ->leftJoin('kategori_rapat','kategori_rapat.id','=','rapat.id_kategori')
            ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
            ->leftJoin('undangan', function($j){ $j->on('undangan.id_rapat','=','rapat.id'); })
            ->leftJoin('absensi',  function($j){ $j->on('absensi.id_rapat','=','rapat.id'); })
            ->select(
                'rapat.id','rapat.judul','rapat.tanggal','rapat.waktu_mulai','rapat.tempat',
                'kategori_rapat.nama as nama_kategori',
                DB::raw('COUNT(DISTINCT undangan.id) as jml_diundang'),
                DB::raw("SUM(CASE WHEN absensi.status='hadir' THEN 1 ELSE 0 END) as jml_hadir"),
                DB::raw("SUM(CASE WHEN absensi.status='tidak_hadir' THEN 1 ELSE 0 END) as jml_tidak_hadir"),
                DB::raw("SUM(CASE WHEN absensi.status='izin' THEN 1 ELSE 0 END) as jml_izin"),
                DB::raw('CASE WHEN MIN(notulensi.id) IS NULL THEN 0 ELSE 1 END as ada_notulensi')
            )
            ->when($dari,   fn($qq)=>$qq->whereDate('rapat.tanggal','>=',$dari))
            ->when($sampai, fn($qq)=>$qq->whereDate('rapat.tanggal','<=',$sampai))
            ->when($id_kat, fn($qq)=>$qq->where('rapat.id_kategori',$id_kat))
            ->groupBy('rapat.id','rapat.judul','rapat.tanggal','rapat.waktu_mulai','rapat.tempat','kategori_rapat.nama');

        if ($status_n === 'sudah')      $q->having('ada_notulensi','=',1);
        elseif ($status_n === 'belum')  $q->having('ada_notulensi','=',0);

        $data = $q->orderBy('rapat.tanggal','desc')->get();

        $pdf = Pdf::loadView('laporan.cetak', [
            'data'   => $data,
            'filter' => compact('dari','sampai'),
        ])->setPaper('a4','portrait');

        return $pdf->stream('Laporan-Rapat-'.date('Ymd_His').'.pdf');
    }

    /** ==========================================
     *  CETAK PDF GABUNGAN (UNDANGAN+ABSENSI+NOTULENSI)
     *  ========================================== */
    public function cetakGabunganRapat($id)
    {
        // 1) Data rapat
        $rapat = DB::table('rapat')
            ->leftJoin('pimpinan_rapat','rapat.id_pimpinan','=','pimpinan_rapat.id')
            ->leftJoin('kategori_rapat','rapat.id_kategori','=','kategori_rapat.id')
            ->select(
                'rapat.*',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            )->where('rapat.id',$id)->first() ?? abort(404);

        // 2) Undangan (pakai users)
        $daftar_peserta = DB::table('undangan')
            ->leftJoin('users','users.id','=','undangan.id_user')
            ->select('undangan.*','users.name as nama', DB::raw('COALESCE(users.jabatan,"") as jabatan'))
            ->where('undangan.id_rapat',$rapat->id)->orderBy('users.name')->get();

        // peserta untuk laporan absensi
        $peserta = DB::table('undangan')
            ->join('users','users.id','=','undangan.id_user')
            ->where('undangan.id_rapat',$rapat->id)
            ->orderBy('users.name')
            ->get(['users.id as id_user','users.name as name', DB::raw('COALESCE(users.jabatan,"") as jabatan')]);

        $pimpinan = (object)[
            'nama'    => $rapat->nama_pimpinan ?? '-',
            'jabatan' => $rapat->jabatan_pimpinan ?? 'Pimpinan Rapat',
        ];

        // 3) Absensi
        $absensi = DB::table('absensi')
            ->leftJoin('users','users.id','=','absensi.id_user')
            ->select('absensi.*','users.name as nama', DB::raw('COALESCE(users.jabatan,"") as jabatan'))
            ->where('absensi.id_rapat',$rapat->id)->orderBy('users.name')->get();

        $rekapAbsensi = [
            'diundang'    => DB::table('undangan')->where('id_rapat',$rapat->id)->count(),
            'hadir'       => DB::table('absensi')->where('id_rapat',$rapat->id)->where('status','hadir')->count(),
            'tidak_hadir' => DB::table('absensi')->where('id_rapat',$rapat->id)->where('status','tidak_hadir')->count(),
            'izin'        => DB::table('absensi')->where('id_rapat',$rapat->id)->where('status','izin')->count(),
        ];

        // 4) Notulensi
        $notulensi = DB::table('notulensi')->where('id_rapat',$rapat->id)->first();
        $detail = collect(); $dokumentasi = collect(); $creator = null;
        $jumlah_peserta = DB::table('undangan')->where('id_rapat',$rapat->id)->count();

        if ($notulensi) {
            $detail = DB::table('notulensi_detail')->where('id_notulensi',$notulensi->id)->orderBy('urut')->get();
            $dokumentasi = DB::table('notulensi_dokumentasi')->where('id_notulensi',$notulensi->id)->get();
            $creator = DB::table('users')->where('id',$notulensi->id_user)->first();
        }

        // 5) Render PDF per bagian
        $tmpDir = storage_path('app'); $files = [];

        // a) Undangan
        $fUnd = $tmpDir.'/undangan-'.Str::random(8).'.pdf';
        Pdf::loadView('rapat.undangan_pdf', [
                'rapat'          => $rapat,
                'daftar_peserta' => $daftar_peserta,
                'pimpinan'       => $pimpinan,
                'kop_path'       => public_path('Screenshot 2025-08-23 121254.jpeg'),
            ])->setPaper('a4','portrait')->save($fUnd);
        $files[] = $fUnd;

        // b) Absensi
        $fAbs = $tmpDir.'/absensi-'.Str::random(8).'.pdf';
        Pdf::loadView('absensi.laporan_pdf', [
                'rapat'    => $rapat,
                'peserta'  => $peserta,
                'absensi'  => $absensi,
                'rekap'    => $rekapAbsensi,
                'pimpinan' => $pimpinan,
                'kop'      => public_path('kop_absen.jpg'),
            ])->setPaper('a4','portrait')->save($fAbs);
        $files[] = $fAbs;

        // c) Notulensi (jika ada)
        if ($notulensi) {
            $dataNot = [
                'notulensi'      => $notulensi,
                'rapat'          => $rapat,
                'detail'         => $detail,
                'dokumentasi'    => $dokumentasi,
                'creator'        => $creator,
                'jumlah_peserta' => $jumlah_peserta,
            ];
            $fP1 = $tmpDir.'/p1-'.Str::random(8).'.pdf';
            Pdf::loadView('notulensi.cetak_p1',$dataNot)->setPaper('a4','portrait')->save($fP1);
            $files[] = $fP1;

            $fP2 = $tmpDir.'/p2-'.Str::random(8).'.pdf';
            Pdf::loadView('notulensi.cetak_p2',$dataNot)->setPaper('a4','landscape')->save($fP2);
            $files[] = $fP2;

            $fP3 = $tmpDir.'/p3-'.Str::random(8).'.pdf';
            Pdf::loadView('notulensi.cetak_p3',$dataNot)->setPaper('a4','portrait')->save($fP3);
            $files[] = $fP3;
        }

        // 6) Merge + kirim
        $merger = new Merger(); foreach ($files as $f) $merger->addFile($f); $merged = $merger->merge();
        foreach ($files as $f) @unlink($f);

        $filename = 'Gabungan-Rapat-'.Str::slug($rapat->judul).'-'.date('Ymd_His').'.pdf';
        return response($merged,200,[
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'inline; filename="'.$filename.'"',
        ]);
    }

    /** =====================
     *  UPLOAD FILE LAPORAN
     *  ===================== */
    public function storeUpload(Request $r)
    {
        $r->validate([
            'judul'            => 'required|string|max:255',
            'tanggal_laporan'  => 'nullable|date',
            'keterangan'       => 'nullable|string',
            'id_kategori'      => 'nullable|integer',
            'file_laporan'     => 'required|file|max:15360', // 15MB
        ]);

        $f = $r->file('file_laporan');
        $dir = 'laporan';
        $nameOnDisk = (string) Str::uuid().'.'.strtolower($f->getClientOriginalExtension());
        $storedPath = $f->storeAs($dir, $nameOnDisk); // storage/app/laporan/...

        $rawKat = $r->input('id_kategori');
        $idKategori = ($rawKat === null || $rawKat === '') ? null : (int)$rawKat;

        DB::table('laporan_files')->insert([
            'id_rapat'        => null,
            'id_kategori'     => $idKategori,
            'judul'           => $r->judul,
            'tanggal_laporan' => $r->tanggal_laporan,
            'keterangan'      => $r->keterangan,
            'file_name'       => $f->getClientOriginalName(),
            'file_path'       => $storedPath,
            'mime'            => $f->getClientMimeType(),
            'size'            => $f->getSize(),
            'uploaded_by'     => Auth::id(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return back()->with('ok','File laporan berhasil diunggah.');
    }

    /** =====================
     *  UPDATE FILE LAPORAN
     *  ===================== */
    public function updateFile(Request $r, $id)
    {
    $r->validate([
        'judul'            => 'required|string|max:255',
        'id_kategori'      => 'nullable|integer',
        'id_rapat'         => 'nullable|integer',
        'tanggal_laporan'  => 'nullable|date',
        'keterangan'       => 'nullable|string',
        'file_laporan'     => 'nullable|file|max:15360', // 15MB
    ]);

    $row = DB::table('laporan_files')->where('id',$id)->first() ?? abort(404);

    $update = [
        'judul'           => $r->judul,
        'id_kategori'     => $r->filled('id_kategori') ? (int)$r->id_kategori : null,
        'id_rapat'        => $r->filled('id_rapat') ? (int)$r->id_rapat : null,
        'tanggal_laporan' => $r->tanggal_laporan ?: null,
        'keterangan'      => $r->keterangan,
        'updated_at'      => now(),
    ];

    if ($r->hasFile('file_laporan')) {
        // hapus file lama
        if (!empty($row->file_path)) {
            @unlink(storage_path('app/'.$row->file_path));
        }
        $f = $r->file('file_laporan');
        $dir = 'laporan';
        $nameOnDisk = (string) Str::uuid().'.'.strtolower($f->getClientOriginalExtension());
        $storedPath = $f->storeAs($dir, $nameOnDisk);
        $update['file_name'] = $f->getClientOriginalName();
        $update['file_path'] = $storedPath;
        $update['mime']      = $f->getClientMimeType();
        $update['size']      = $f->getSize();
    }

    DB::table('laporan_files')->where('id',$id)->update($update);

    return back()->with('ok','Laporan diperbarui.');
    }

    /** =====================
     *  DOWNLOAD & DELETE
     *  ===================== */
    public function downloadFile($id)
    {
        $row = DB::table('laporan_files')->where('id',$id)->first() ?? abort(404);
        $abs = storage_path('app/'.$row->file_path);
        if (!is_file($abs)) abort(404,'File tidak ditemukan');

        return response()->download($abs, $row->file_name, [
            'Content-Type' => $row->mime ?: 'application/octet-stream'
        ]);
    }

    public function destroyFile($id)
    {
        $row = DB::table('laporan_files')->where('id',$id)->first() ?? abort(404);
        @unlink(storage_path('app/'.$row->file_path));
        DB::table('laporan_files')->where('id',$id)->delete();

        return back()->with('ok','File laporan dihapus.');
    }

        private function hitungBadgeLaporan(): array
    {
        $nowY = date('Y'); $nowM = date('m');

        $baru  = DB::table('laporan_files')
            ->whereYear('created_at', $nowY)
            ->whereMonth('created_at', $nowM)
            ->count();

        $arsip = DB::table('laporan_files')
            ->where(function ($q) use ($nowY, $nowM) {
                $q->whereYear('created_at', '<', $nowY)
                  ->orWhere(function($qq) use ($nowY,$nowM){
                      $qq->whereYear('created_at', $nowY)
                         ->whereMonth('created_at', '<', $nowM);
                  });
            })
            ->count();

        return ['baru' => $baru, 'arsip' => $arsip];
    }

    /** Halaman: Laporan Baru (bulan berjalan) */
    public function baru(Request $request)
    {
        // ===== Filter input =====
        $dari     = $request->get('dari');            // yyyy-mm-dd
        $sampai   = $request->get('sampai');          // yyyy-mm-dd
        $id_kat   = $request->get('id_kategori');     // id kategori rapat
        $status_n = $request->get('status_notulensi'); // 'sudah' | 'belum' | null

        // ===== Query rekap per rapat (seperti screenshot) =====
        $q = DB::table('rapat')
            ->leftJoin('kategori_rapat','kategori_rapat.id','=','rapat.id_kategori')
            ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
            ->leftJoin('undangan', function($j){ $j->on('undangan.id_rapat','=','rapat.id'); })
            ->leftJoin('absensi',  function($j){ $j->on('absensi.id_rapat','=','rapat.id'); })
            ->select(
                'rapat.id',
                'rapat.judul',
                'rapat.tanggal',
                'rapat.waktu_mulai',
                'rapat.tempat',
                'kategori_rapat.nama as nama_kategori',
                // indikator notulensi ada/belum
                DB::raw('CASE WHEN MIN(notulensi.id) IS NULL THEN 0 ELSE 1 END as ada_notulensi')
            )
            ->when($dari,   fn($qq)=>$qq->whereDate('rapat.tanggal','>=',$dari))
            ->when($sampai, fn($qq)=>$qq->whereDate('rapat.tanggal','<=',$sampai))
            ->when($id_kat, fn($qq)=>$qq->where('rapat.id_kategori',$id_kat))
            ->groupBy('rapat.id','rapat.judul','rapat.tanggal','rapat.waktu_mulai','rapat.tempat','kategori_rapat.nama');

        // filter status notulensi
        if ($status_n === 'sudah') {
            $q->having('ada_notulensi','=',1);
        } elseif ($status_n === 'belum') {
            $q->having('ada_notulensi','=',0);
        }

        $data = $q->orderBy('rapat.tanggal','desc')->get();

        // dropdown kategori
        $kategori = DB::table('kategori_rapat')->select('id','nama')->orderBy('nama')->get();

        return view('laporan.baru', [
            'data'     => $data,
            'kategori' => $kategori,
            'filter'   => compact('dari','sampai','id_kat','status_n'),
        ]);
    }

    /** Halaman: Arsip Laporan (bulan sebelumnya) + tombol upload */
    public function arsip(Request $r)
    {
    $dari     = $r->get('dari');          // yyyy-mm-dd (opsional)
    $sampai   = $r->get('sampai');        // yyyy-mm-dd (opsional)
    $id_kat   = $r->get('id_kategori');   // opsional
    $qsearch  = $r->get('q');             // pencarian judul/keterangan (opsional)

    // pakai COALESCE(tanggal_laporan, created_at) sebagai anchor tanggal filter
    $dateExpr = DB::raw('COALESCE(laporan_files.tanggal_laporan, laporan_files.created_at)');

    // === Jika user tidak isi filter manual, default ambil bulan lalu ===
    if (!$dari && !$sampai) {
        $dari   = now()->subMonth()->startOfMonth()->format('Y-m-d');
        $sampai = now()->subMonth()->endOfMonth()->format('Y-m-d');
    }

    $uploads = DB::table('laporan_files')
        ->leftJoin('rapat','rapat.id','=','laporan_files.id_rapat')
        ->leftJoin('kategori_rapat','kategori_rapat.id','=','laporan_files.id_kategori')
        ->select(
            'laporan_files.*',
            'rapat.judul as judul_rapat',
            'kategori_rapat.nama as nama_kategori'
        )
        ->when($dari,   fn($qq)=>$qq->whereDate($dateExpr,'>=',$dari))
        ->when($sampai, fn($qq)=>$qq->whereDate($dateExpr,'<=',$sampai))
        ->when($id_kat, fn($qq)=>$qq->where('laporan_files.id_kategori',$id_kat))
        ->when($qsearch, function($qq) use ($qsearch){
            $qq->where(function($w) use ($qsearch){
                $w->where('laporan_files.judul','like',"%$qsearch%")
                  ->orWhere('laporan_files.keterangan','like',"%$qsearch%");
            });
        })
        ->orderBy('laporan_files.created_at','desc')
        ->get();

    $kategori = DB::table('kategori_rapat')->select('id','nama')->orderBy('nama')->get();
    $rapatList = DB::table('rapat')->select('id','judul','tanggal')->orderBy('tanggal','desc')->get();

    return view('laporan.arsip', [
        'uploads'   => $uploads,
        'kategori'  => $kategori,
        'rapatList' => $rapatList,
        'filter'    => compact('dari','sampai','id_kat','qsearch'),
    ]);
}
}