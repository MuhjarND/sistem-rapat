<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use iio\libmergepdf\Merger;

class LaporanController extends Controller
{
    /** =========================
     *  LIST + FILTER + UPLOADS (Laporan Baru/Aktif)
     *  ========================= */
    public function index(Request $request)
    {
        // ===== Filter dari form =====
        $dari       = $request->get('dari');
        $sampai     = $request->get('sampai');
        $id_kat_in  = $request->get('id_kat', $request->get('id_kategori'));
        $id_rapat   = $request->get('id_rapat');
        $qsearch    = $request->get('qsearch');
        $status_n   = $request->get('status_notulensi');

        // ===== Rekap Rapat (EXCLUDE yang sudah diarsip) =====
        $q = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'kategori_rapat.id', '=', 'rapat.id_kategori')
            ->leftJoin('notulensi', 'notulensi.id_rapat', '=', 'rapat.id')
            ->leftJoin('undangan',  'undangan.id_rapat',  '=', 'rapat.id')
            ->leftJoin('absensi',   'absensi.id_rapat',   '=', 'rapat.id')
            ->leftJoin('laporan_archived_meetings as lam', 'lam.rapat_id', '=', 'rapat.id')
            ->leftJoin('laporan_files', function ($j) {
                $j->on('laporan_files.id_rapat', '=', 'rapat.id')
                  ->where('laporan_files.is_archived', 0);
            })
            ->select(
                'rapat.id',
                'rapat.judul',
                'rapat.tanggal',
                'rapat.waktu_mulai',
                'rapat.tempat',
                'kategori_rapat.nama as nama_kategori',
                DB::raw('COUNT(DISTINCT undangan.id) as jml_diundang'),
                DB::raw("SUM(CASE WHEN absensi.status='hadir' THEN 1 ELSE 0 END) as jml_hadir"),
                DB::raw('CASE WHEN MIN(notulensi.id) IS NULL THEN 0 ELSE 1 END as ada_notulensi'),
                DB::raw('COUNT(DISTINCT laporan_files.id) as jml_files_aktif')
            )
            ->whereNull('lam.id')
            ->when($dari,       fn ($qq) => $qq->whereDate('rapat.tanggal', '>=', $dari))
            ->when($sampai,     fn ($qq) => $qq->whereDate('rapat.tanggal', '<=', $sampai))
            ->when($id_kat_in,  fn ($qq) => $qq->where('rapat.id_kategori', $id_kat_in))
            ->groupBy('rapat.id', 'rapat.judul', 'rapat.tanggal', 'rapat.waktu_mulai', 'rapat.tempat', 'kategori_rapat.nama');

        if ($status_n === 'sudah') {
            $q->having('ada_notulensi', '=', 1);
        } elseif ($status_n === 'belum') {
            $q->having('ada_notulensi', '=', 0);
        }

        $rekap = $q->orderBy('rapat.tanggal', 'desc')
                   ->paginate(10, ['*'], 'rekap_page')
                   ->appends($request->query());

        // ===== Dropdown =====
        $kategori  = DB::table('kategori_rapat')->select('id', 'nama')->orderBy('nama')->get();
        $rapatList = DB::table('rapat')->select('id', 'judul', 'tanggal')->orderBy('tanggal', 'desc')->get();

        // ===== Daftar Upload (aktif / belum diarsip) =====
        $dateExpr = DB::raw('COALESCE(laporan_files.tanggal_laporan, laporan_files.created_at)');

        $uploadsQ = DB::table('laporan_files')
            ->leftJoin('rapat', 'rapat.id', '=', 'laporan_files.id_rapat')
            ->leftJoin('kategori_rapat', 'kategori_rapat.id', '=', 'laporan_files.id_kategori')
            ->select(
                'laporan_files.*',
                'rapat.judul as judul_rapat',
                'rapat.tanggal as tgl_rapat',
                'kategori_rapat.nama as nama_kategori'
            )
            ->where('laporan_files.is_archived', 0)
            ->when($dari,      fn ($qq) => $qq->whereDate($dateExpr, '>=', $dari))
            ->when($sampai,    fn ($qq) => $qq->whereDate($dateExpr, '<=', $sampai))
            ->when($id_kat_in, fn ($qq) => $qq->where('laporan_files.id_kategori', $id_kat_in))
            ->when($id_rapat,  fn ($qq) => $qq->where('laporan_files.id_rapat', $id_rapat))
            ->when($qsearch, function ($qq) use ($qsearch) {
                $qq->where(function ($w) use ($qsearch) {
                    $w->where('laporan_files.judul', 'like', "%$qsearch%")
                      ->orWhere('laporan_files.keterangan', 'like', "%$qsearch%")
                      ->orWhere('laporan_files.file_name', 'like', "%$qsearch%");
                });
            })
            ->orderBy('laporan_files.created_at', 'desc');

        $uploads = $uploadsQ->paginate(10, ['*'], 'file_page')
                            ->appends($request->query());

        /* =========================
         * BADGE untuk sidebar/menu
         * =========================
         * Sesuai permintaan: jumlah di badge "Laporan" = total baris tabel Rekap + total baris tabel Unggahan (aktif)
         */
        $badgeActive  = $rekap->total() + $uploads->total();
        // (Opsional) jika ingin menampilkan badge arsip juga di sidebar saat berada di halaman index:
        $badgeArchive = DB::table('laporan_files')->where('is_archived', 1)->count();

        return view('laporan.index', [
            'rekap'     => $rekap,
            'kategori'  => $kategori,
            'filter'    => [
                'dari'      => $dari,
                'sampai'    => $sampai,
                'id_kat'    => $id_kat_in,
                'id_rapat'  => $id_rapat,
                'qsearch'   => $qsearch,
                'status_n'  => $status_n,
            ],
            'uploads'   => $uploads,
            'rapatList' => $rapatList,
            'badge'     => [
                'active'  => $badgeActive,
                'archive' => $badgeArchive,
            ],
        ]);
    }

    /** ============= CETAK REKAP ============= */
    public function cetak(Request $request)
    {
        $dari     = $request->get('dari');
        $sampai   = $request->get('sampai');
        $id_kat   = $request->get('id_kategori', $request->get('id_kat'));
        $status_n = $request->get('status_notulensi');

        $q = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'kategori_rapat.id', '=', 'rapat.id_kategori')
            ->leftJoin('notulensi', 'notulensi.id_rapat', '=', 'rapat.id')
            ->leftJoin('undangan', fn($j) => $j->on('undangan.id_rapat', '=', 'rapat.id'))
            ->leftJoin('absensi',  fn($j) => $j->on('absensi.id_rapat',  '=', 'rapat.id'))
            ->leftJoin('laporan_archived_meetings as lam', fn($j) => $j->on('lam.rapat_id', '=', 'rapat.id'))
            ->select(
                'rapat.id',
                'rapat.judul',
                'rapat.tanggal',
                'rapat.waktu_mulai',
                'rapat.tempat',
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
            ->whereNull('lam.id')
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

    /** ===== CETAK PDF GABUNGAN (UNDANGAN+ABSENSI+NOTULENSI) ===== */
    public function cetakGabunganRapat($id)
    {
        $binary = $this->renderGabunganPdfBinary($id);
        $filename = 'Gabungan-Rapat-'.Str::slug(optional(DB::table('rapat')->find($id))->judul ?? 'rapat').'-'.date('Ymd_His').'.pdf';

        return response($binary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /** =====================  UPLOAD FILE LAPORAN  ===================== */
    public function storeUpload(Request $r)
    {
        $r->validate([
            'judul'            => 'required|string|max:255',
            'tanggal_laporan'  => 'nullable|date',
            'keterangan'       => 'nullable|string',
            'id_kategori'      => 'nullable|integer',
            'id_rapat'         => 'nullable|integer',
            'file_laporan'     => 'required|file|max:15360',
            'bucket'           => 'nullable|in:aktif,arsip',
        ]);

        $f = $r->file('file_laporan');
        $dir = 'laporan';
        $nameOnDisk = (string) Str::uuid().'.'.strtolower($f->getClientOriginalExtension());
        $storedPath = $f->storeAs($dir, $nameOnDisk);

        $rawKat      = $r->input('id_kategori');
        $idKategori  = ($rawKat === null || $rawKat === '') ? null : (int)$rawKat;
        $idRapat     = $r->filled('id_rapat') ? (int)$r->id_rapat : null;
        $bucket      = $r->input('bucket','aktif');
        $isArchived  = $bucket === 'arsip' ? 1 : 0;
        $archivedAt  = $isArchived ? now() : null;

        DB::table('laporan_files')->insert([
            'id_rapat'        => $idRapat,
            'id_kategori'     => $idKategori,
            'judul'           => $r->judul,
            'tanggal_laporan' => $r->tanggal_laporan,
            'keterangan'      => $r->keterangan,
            'file_name'       => $f->getClientOriginalName(),
            'file_path'       => $storedPath,
            'mime'            => $f->getClientMimeType(),
            'size'            => $f->getSize(),
            'uploaded_by'     => Auth::id(),
            'is_archived'     => $isArchived,
            'archived_at'     => $archivedAt,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return back()->with('ok','File laporan berhasil diunggah.');
    }

    /** =====================  UPDATE FILE LAPORAN  ===================== */
    public function updateFile(Request $r, $id)
    {
        $r->validate([
            'judul'            => 'required|string|max:255',
            'id_kategori'      => 'nullable|integer',
            'id_rapat'         => 'nullable|integer',
            'tanggal_laporan'  => 'nullable|date',
            'keterangan'       => 'nullable|string',
            'file_laporan'     => 'nullable|file|max:15360',
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
            if (!empty($row->file_path)) @unlink(storage_path('app/'.$row->file_path));

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

    /** =====================  DOWNLOAD & DELETE  ===================== */
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

    /** =====================  ARSIP LAPORAN  ===================== */
    public function arsip(Request $r)
    {
        $dari     = $r->get('dari');          // opsional
        $sampai   = $r->get('sampai');        // opsional
        $id_kat   = $r->get('id_kategori');   // opsional
        $id_rapat = $r->get('id_rapat');      // opsional
        $qsearch  = $r->get('qsearch');       // opsional

        $uploadsQ = DB::table('laporan_files')
            ->leftJoin('rapat','rapat.id','=','laporan_files.id_rapat')
            ->leftJoin('kategori_rapat','kategori_rapat.id','=','laporan_files.id_kategori')
            ->select(
                'laporan_files.*',
                'rapat.judul as judul_rapat',
                'rapat.tanggal as tgl_rapat',
                'kategori_rapat.nama as nama_kategori'
            )
            ->where('laporan_files.is_archived', 1)
            ->when($dari,   fn($qq)=>$qq->whereDate('laporan_files.tanggal_laporan','>=',$dari))
            ->when($sampai, fn($qq)=>$qq->whereDate('laporan_files.tanggal_laporan','<=',$sampai))
            ->when($id_kat, fn($qq)=>$qq->where('laporan_files.id_kategori',$id_kat))
            ->when($id_rapat, fn($qq)=>$qq->where('laporan_files.id_rapat',$id_rapat))
            ->when($qsearch, function($qq) use ($qsearch){
                $qq->where(function($w) use ($qsearch){
                    $w->where('laporan_files.judul','like',"%$qsearch%")
                      ->orWhere('laporan_files.keterangan','like',"%$qsearch%")
                      ->orWhere('laporan_files.file_name','like',"%$qsearch%");
                });
            })
            ->orderBy('laporan_files.archived_at','desc');

        $uploads = $uploadsQ->paginate(7)->appends($r->query());

        // Badge: di halaman arsip, jadikan badge arsip = total paginator arsip,
        // dan badge aktif dihitung cepat (agar sidebar tetap tampil informatif).
        $badgeArchive = $uploads->total();
        $badgeActive  = DB::table('rapat')
                            ->leftJoin('laporan_archived_meetings as lam','lam.rapat_id','=','rapat.id')
                            ->whereNull('lam.id')
                            ->count()
                        + DB::table('laporan_files')->where('is_archived',0)->count();

        $kategori = DB::table('kategori_rapat')->select('id','nama')->orderBy('nama')->get();
        $rapatList = DB::table('rapat')->select('id','judul','tanggal')->orderBy('tanggal','desc')->get();

        return view('laporan.arsip', [
            'uploads'   => $uploads,
            'kategori'  => $kategori,
            'rapatList' => $rapatList,
            'filter'    => compact('dari','sampai','id_kat','id_rapat','qsearch'),
            'badge'     => [
                'active'  => $badgeActive,
                'archive' => $badgeArchive,
            ],
        ]);
    }

    /** ======== PINDAH BUCKET: ARSIPKAN / PULIHKAN ======== */
    public function archiveFile($id)
    {
        DB::table('laporan_files')->where('id',$id)->update([
            'is_archived' => 1,
            'archived_at' => now(),
            'updated_at'  => now(),
        ]);
        return back()->with('ok','Laporan berhasil diarsipkan.');
    }

    public function unarchiveFile($id)
    {
        DB::table('laporan_files')->where('id', $id)->update([
            'is_archived' => 0,
            'archived_at' => null,
            'updated_at'  => now()
        ]);
        return back()->with('ok', 'Laporan dipulihkan dari arsip.');
    }

    /**
     * Arsipkan SEMUA file aktif milik rapat.
     * Jika TIDAK ADA file aktif, otomatis buat PDF gabungan (undangan+absensi+notulensi jika ada),
     * simpan ke storage, dan masukkan ke arsip.
     */
    public function archiveRapat($id)
    {
        DB::beginTransaction();

        try {
            // 1) Arsipkan file aktif yang terkait rapat
            $affected = DB::table('laporan_files')
                ->where('id_rapat', $id)
                ->where('is_archived', 0)
                ->update([
                    'is_archived' => 1,
                    'archived_at' => now(),
                    'updated_at'  => now(),
                ]);

            // 2) Jika tidak ada file aktif: buat PDF gabungan dan masukkan ke arsip
            if ($affected == 0) {
                $rapat = DB::table('rapat')
                    ->leftJoin('kategori_rapat','kategori_rapat.id','=','rapat.id_kategori')
                    ->select('rapat.*','kategori_rapat.id as id_kategori')
                    ->where('rapat.id',$id)
                    ->first();

                if (!$rapat) {
                    DB::rollBack();
                    return back()->with('error','Rapat tidak ditemukan.');
                }

                $binary   = $this->renderGabunganPdfBinary($id);
                $diskName = (string) Str::uuid().'.pdf';
                $path     = 'laporan/'.$diskName;
                Storage::put($path, $binary);

                $judulFile  = $rapat->judul;
                $fileName   = Str::slug($rapat->judul).'.pdf';
                $fileSize   = strlen($binary);

                DB::table('laporan_files')->insert([
                    'id_rapat'        => $rapat->id,
                    'id_kategori'     => $rapat->id_kategori ?: null,
                    'judul'           => $judulFile,
                    'tanggal_laporan' => $rapat->tanggal,
                    'keterangan'      => '(Undangan + Absensi'.(DB::table('notulensi')->where('id_rapat',$rapat->id)->exists() ? ' + Notulensi' : '').')',
                    'file_name'       => $fileName,
                    'file_path'       => $path,
                    'mime'            => 'application/pdf',
                    'size'            => $fileSize,
                    'uploaded_by'     => Auth::id(),
                    'is_archived'     => 1,
                    'archived_at'     => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            // 3) Tandai rapat sudah diarsip agar tersembunyi dari rekap
            DB::table('laporan_archived_meetings')->updateOrInsert(
                ['rapat_id' => $id],
                ['archived_at' => now(), 'created_at' => now(), 'updated_at' => now()]
            );

            DB::commit();

            if ($affected == 0) {
                return back()->with('ok','Tidak ada file aktif. PDF gabungan dibuat dan dimasukkan ke Arsip.');
            }

            return back()->with('ok', "Berhasil mengarsipkan {$affected} dokumen terkait rapat.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error','Gagal mengarsipkan rapat: '.$e->getMessage());
        }
    }

    /* ==========================================================
     * Helper: render PDF gabungan (binary string) agar reusable
     * Dipakai oleh cetakGabunganRapat() dan archiveRapat()
     * ========================================================== */
    private function renderGabunganPdfBinary(int $rapatId): string
    {
        $rapat = DB::table('rapat')
            ->leftJoin('pimpinan_rapat','rapat.id_pimpinan','=','pimpinan_rapat.id')
            ->leftJoin('kategori_rapat','rapat.id_kategori','=','kategori_rapat.id')
            ->select(
                'rapat.*',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            )->where('rapat.id',$rapatId)->first() ?? abort(404);

        $daftar_peserta = DB::table('undangan')
            ->leftJoin('users','users.id','=','undangan.id_user')
            ->select('undangan.*','users.name as nama', DB::raw('COALESCE(users.jabatan,"") as jabatan'))
            ->where('undangan.id_rapat',$rapat->id)->orderBy('users.name')->get();

        $peserta = DB::table('undangan')
            ->join('users','users.id','=','undangan.id_user')
            ->where('undangan.id_rapat',$rapat->id)
            ->orderBy('users.name')
            ->get(['users.id as id_user','users.name as name', DB::raw('COALESCE(users.jabatan,"") as jabatan')]);

        $pimpinan = (object)[
            'nama'    => $rapat->nama_pimpinan ?? '-',
            'jabatan' => $rapat->jabatan_pimpinan ?? 'Pimpinan Rapat',
        ];

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

        $notulensi = DB::table('notulensi')->where('id_rapat',$rapat->id)->first();
        $detail = collect(); $dokumentasi = collect(); $creator = null;
        $jumlah_peserta = DB::table('undangan')->where('id_rapat',$rapat->id)->count();

        if ($notulensi) {
            $detail = DB::table('notulensi_detail')->where('id_notulensi',$notulensi->id)->orderBy('urut')->get();
            $dokumentasi = DB::table('notulensi_dokumentasi')->where('id_notulensi',$notulensi->id)->get();
            $creator = DB::table('users')->where('id',$notulensi->id_user)->first();
        }

        $tmpDir = storage_path('app'); $files = [];

        // Undangan
        $fUnd = $tmpDir.'/und-'.Str::random(8).'.pdf';
        Pdf::loadView('rapat.undangan_pdf', [
            'rapat'          => $rapat,
            'daftar_peserta' => $daftar_peserta,
            'pimpinan'       => $pimpinan,
            'kop_path'       => public_path('Screenshot 2025-08-23 121254.jpeg'),
        ])->setPaper('a4','portrait')->save($fUnd);
        $files[] = $fUnd;

        // Absensi
        $fAbs = $tmpDir.'/abs-'.Str::random(8).'.pdf';
        Pdf::loadView('absensi.laporan_pdf', [
            'rapat'    => $rapat,
            'peserta'  => $peserta,
            'absensi'  => $absensi,
            'rekap'    => $rekapAbsensi,
            'pimpinan' => $pimpinan,
            'kop'      => public_path('kop_absen.jpg'),
        ])->setPaper('a4','portrait')->save($fAbs);
        $files[] = $fAbs;

        // Notulensi (opsional)
        if ($notulensi) {
            $dataNot = compact('notulensi','rapat','detail','dokumentasi','creator','jumlah_peserta');

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

        $merger = new Merger();
        foreach ($files as $f) $merger->addFile($f);
        $merged = $merger->merge();

        foreach ($files as $f) @unlink($f);

        return $merged;
    }
}
