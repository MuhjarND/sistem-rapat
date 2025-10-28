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
        // gunakan COALESCE utk filter tanggal
        $dateExpr = DB::raw('COALESCE(laporan_files.tanggal_laporan, laporan_files.created_at)');

        $uploadsQ = DB::table('laporan_files')
            ->leftJoin('rapat', 'rapat.id', '=', 'laporan_files.id_rapat')
            ->leftJoin('kategori_rapat', 'kategori_rapat.id', '=', 'laporan_files.id_kategori')
            ->select(
                'laporan_files.*',
                // data rapat utk kolom Detail
                'rapat.judul as judul_rapat',
                'rapat.tanggal as tanggal_rapat',
                'rapat.waktu_mulai',
                'rapat.tempat',
                // kategori
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
         * ========================= */
        $badgeActive  = $rekap->total() + $uploads->total();
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
                // data rapat utk kolom Detail
                'rapat.judul as judul_rapat',
                'rapat.tanggal as tanggal_rapat',
                'rapat.waktu_mulai',
                'rapat.tempat',
                // kategori
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

        // Badge
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

        // ===== Peserta (urut hirarki) =====
        $daftar_peserta = DB::table('undangan')
            ->join('users','users.id','=','undangan.id_user')
            ->where('undangan.id_rapat',$rapat->id)
            ->select('users.id','users.name', DB::raw('COALESCE(users.jabatan,"") as jabatan'), 'users.hirarki')
            ->orderByRaw('COALESCE(users.hirarki, 9999) ASC')
            ->orderBy('users.name','asc')
            ->get();

        $tampilkan_lampiran        = $daftar_peserta->count() > 5;
        $tampilkan_daftar_di_surat = !$tampilkan_lampiran;

        // ===== Approval & QR: UNDANGAN =====
        $approval1 = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
        $approval2 = $rapat->approval2_user_id
            ? DB::table('users')->where('id', $rapat->approval2_user_id)->first()
            : null;

        $qrA1 = DB::table('approval_requests')
            ->where('rapat_id', $rapat->id)
            ->where('doc_type', 'undangan')
            ->where('approver_user_id', $rapat->approval1_user_id)
            ->where('status', 'approved')
            ->orderByDesc('signed_at')
            ->value('signature_qr_path');

        $qrA2 = null;
        if (!empty($rapat->approval2_user_id)) {
            $qrA2 = DB::table('approval_requests')
                ->where('rapat_id', $rapat->id)
                ->where('doc_type', 'undangan')
                ->where('approver_user_id', $rapat->approval2_user_id)
                ->where('status', 'approved')
                ->orderByDesc('signed_at')
                ->value('signature_qr_path');
        }

        // ===== Approval & QR: ABSENSI =====
        $absensiReq = DB::table('approval_requests')
            ->where('rapat_id', $rapat->id)
            ->where('doc_type', 'absensi')
            ->where('approver_user_id', $rapat->approval1_user_id)
            ->where('status', 'approved')
            ->orderByDesc('signed_at')
            ->first();

        $absensi_qr_data = null; $absensi_qr_web = null; $absensi_qr_fs = null;
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

        // ===== APPROVAL NOTULENSI + QR (lengkap) =====
        $notulensiApprovals = DB::table('approval_requests as ar')
            ->leftJoin('users as u','u.id','=','ar.approver_user_id')
            ->where('ar.rapat_id', $rapat->id)
            ->where('ar.doc_type', 'notulensi')
            ->orderBy('ar.order_index')
            ->get([
                'ar.order_index',
                'ar.status',
                'ar.signed_at',
                'ar.rejected_at',
                'ar.rejection_note',
                'ar.signature_qr_path',
                'u.name as approver_name',
                DB::raw('COALESCE(u.jabatan,"") as approver_jabatan')
            ]);

        $notulensi_approvals = $notulensiApprovals->map(function($r){
            $qr_data = null; $qr_web = null; $qr_fs = null;
            if (!empty($r->signature_qr_path)) {
                $qr_fs = public_path($r->signature_qr_path);
                if (is_file($qr_fs)) {
                    $qr_data = 'data:image/png;base64,'.base64_encode(@file_get_contents($qr_fs));
                    $qr_web  = url($r->signature_qr_path);
                }
            }
            return [
                'order'     => (int)$r->order_index,
                'status'    => $r->status, // approved|pending|blocked|rejected
                'signed_at' => $r->signed_at,
                'name'      => $r->approver_name ?: 'Approver',
                'jabatan'   => $r->approver_jabatan ?: 'Pejabat',
                'qr_data'   => $qr_data,
                'qr_web'    => $qr_web,
                'qr_fs'     => $qr_fs,
            ];
        })->values()->all();

        // Kompat lama (array path saja)
        $notulensi_qr_paths = $notulensiApprovals->pluck('signature_qr_path')->filter()->values()->all();

        // ===== Data tambahan Absensi & Notulensi =====
        $pesertaMinimal = $daftar_peserta->map(fn($r)=>(object)['id_user'=>$r->id,'name'=>$r->name,'jabatan'=>$r->jabatan]);

        $pimpinan = (object)[
            'nama'    => $rapat->nama_pimpinan ?? '-',
            'jabatan' => $rapat->jabatan_pimpinan ?? 'Pimpinan Rapat',
        ];

        $absensi = DB::table('absensi')
            ->leftJoin('users','users.id','=','absensi.id_user')
            ->select('absensi.*','users.name as nama', DB::raw('COALESCE(users.jabatan,"") as jabatan'))
            ->where('absensi.id_rapat',$rapat->id)
            ->orderByRaw('COALESCE(users.hirarki, 9999) ASC')
            ->orderBy('users.name','asc')
            ->get();

        $rekapAbsensi = [
            'diundang'    => $daftar_peserta->count(),
            'hadir'       => DB::table('absensi')->where('id_rapat',$rapat->id)->where('status','hadir')->count(),
            'tidak_hadir' => DB::table('absensi')->where('id_rapat',$rapat->id)->where('status','tidak_hadir')->count(),
            'izin'        => DB::table('absensi')->where('id_rapat',$rapat->id)->where('status','izin')->count(),
        ];

        $notulensi = DB::table('notulensi')->where('id_rapat',$rapat->id)->first();
        $detail = collect(); $dokumentasi = collect(); $creator = null;
        $jumlah_peserta = $daftar_peserta->count();

        if ($notulensi) {
            $detail = DB::table('notulensi_detail')->where('id_notulensi',$notulensi->id)->orderBy('urut')->get();
            $dokumentasi = DB::table('notulensi_dokumentasi')->where('id_notulensi',$notulensi->id)->get();
            $creator = DB::table('users')->where('id',$notulensi->id_user)->first();
        }

        // === Variabel khusus yang dibutuhkan cetak_p2
        $notulis_nama    = $creator->name    ?? ($creator->nama ?? 'Notulis');
        $notulis_jabatan = $creator->jabatan ?? 'Notulis';

        $qr_notulis_data = null;
        $qr_notulis_fs   = null;
        if ($notulensi) {
            foreach (['signature_qr_path','qr_notulis_path','notulis_qr_path'] as $col) {
                if (!empty($notulensi->{$col})) {
                    $qr_notulis_fs = public_path($notulensi->{$col});
                    if (is_file($qr_notulis_fs)) {
                        $qr_notulis_data = 'data:image/png;base64,'.base64_encode(@file_get_contents($qr_notulis_fs));
                        break;
                    }
                }
            }
            if (!$qr_notulis_data && !empty($notulensi->id_user)) {
                $notulisReq = DB::table('approval_requests')
                    ->where('rapat_id', $rapat->id)
                    ->where('doc_type', 'notulensi')
                    ->where('approver_user_id', $notulensi->id_user)
                    ->where('status', 'approved')
                    ->orderByDesc('signed_at')
                    ->first();
                if ($notulisReq && $notulisReq->signature_qr_path) {
                    $qr_notulis_fs = public_path($notulisReq->signature_qr_path);
                    if (is_file($qr_notulis_fs)) {
                        $qr_notulis_data = 'data:image/png;base64,'.base64_encode(@file_get_contents($qr_notulis_fs));
                    }
                }
            }
        }

        $pimpinan_nama  = ($approval1->name ?? 'Approval 1');
        $qr_pimpinan_data = null;
        $pimpinanReq = DB::table('approval_requests')
            ->where('rapat_id', $rapat->id)
            ->where('doc_type', 'notulensi')
            ->where('approver_user_id', $rapat->approval1_user_id)
            ->where('status', 'approved')
            ->orderByDesc('signed_at')
            ->first();
        if ($pimpinanReq && $pimpinanReq->signature_qr_path) {
            $qr_pimpinan_fs = public_path($pimpinanReq->signature_qr_path);
            if (is_file($qr_pimpinan_fs)) {
                $qr_pimpinan_data = 'data:image/png;base64,'.base64_encode(@file_get_contents($qr_pimpinan_fs));
            }
        }

        $tmpDir = storage_path('app'); $files = [];

        // =============== 1) Undangan ===============
        $fUnd = $tmpDir.'/und-'.Str::random(8).'.pdf';
        Pdf::loadView('rapat.undangan_pdf', [
            'rapat'                      => $rapat,
            'daftar_peserta'             => $daftar_peserta,
            'kop_path'                   => public_path('Screenshot 2025-08-23 121254.jpeg'),
            'tampilkan_lampiran'         => $tampilkan_lampiran,
            'tampilkan_daftar_di_surat'  => $tampilkan_daftar_di_surat,
            'approval1'                  => $approval1,
            'approval2'                  => $approval2,
            'qrA1'                       => $qrA1,
            'qrA2'                       => $qrA2,
        ])->setPaper('a4','portrait')->save($fUnd);
        $files[] = $fUnd;

        // =============== 2) Absensi ===============
        $pesertaRows = DB::table('undangan as u')
            ->join('users as usr', 'usr.id', '=', 'u.id_user')
            ->leftJoin('absensi as a', function($q) use ($rapat) {
                $q->on('a.id_user', '=', 'u.id_user')
                  ->where('a.id_rapat', '=', $rapat->id);
            })
            ->where('u.id_rapat', $rapat->id)
            ->select(
                'usr.name',
                DB::raw('COALESCE(usr.jabatan,"") as jabatan'),
                'a.status',
                'a.waktu_absen',
                'a.ttd_path'
            )
            ->orderByRaw('COALESCE(usr.hirarki, 9999) ASC')
            ->orderBy('usr.name','asc')
            ->get();

        $peserta = [];
        foreach ($pesertaRows as $row) {
            $ttd_data = null;
            if (!empty($row->ttd_path)) {
                $fs = public_path($row->ttd_path);
                if (is_file($fs)) {
                    $ttd_data = 'data:image/png;base64,'.base64_encode(@file_get_contents($fs));
                }
            }
            $waktu_absen_fmt = null;
            if (!empty($row->waktu_absen)) {
                try {
                    $waktu_absen_fmt = Carbon::parse($row->waktu_absen)->format('d/m/Y H:i');
                } catch (\Throwable $e) {
                    $waktu_absen_fmt = $row->waktu_absen;
                }
            }
            $peserta[] = [
                'name'        => $row->name,
                'jabatan'     => $row->jabatan ?: '-',
                'status'      => $row->status ?: '-',
                'waktu_absen' => $waktu_absen_fmt,
                'ttd_data'    => $ttd_data,
            ];
        }

        $qrSrc = null;
        if (!empty($absensi_qr_data)) {
            $qrSrc = $absensi_qr_data;
        } elseif (!empty($absensi_qr_fs) && is_file($absensi_qr_fs)) {
            $qrSrc = 'data:image/png;base64,'.base64_encode(@file_get_contents($absensi_qr_fs));
        }

        $approver = [
            'nama'    => $approver_final_nama ?: '-',
            'jabatan' => $approver_final_jabatan ?: 'Penanggung Jawab',
        ];

        Carbon::setLocale('id');
        $rap = [
            'nama_kategori' => $rapat->nama_kategori ?? '-',
            'judul'         => $rapat->judul ?? '-',
            'tanggal_human' => (function($tgl){
                if (!$tgl) return '-';
                try {
                    return Carbon::parse($tgl)->isoFormat('dddd, D MMMM Y');
                } catch (\Throwable $e) {
                    return $tgl;
                }
            })($rapat->tanggal ?? null),
            'waktu_mulai'   => $rapat->waktu_mulai ?? null,
            'tempat'        => $rapat->tempat ?? '-',
        ];

        $fAbs = $tmpDir.'/abs-'.Str::random(8).'.pdf';
        Pdf::loadView('absensi.laporan_pdf', [
            'rap'      => $rap,
            'peserta'  => $peserta,
            'rekap'    => $rekapAbsensi,
            'qrSrc'    => $qrSrc,
            'approver' => $approver,
            'kop'      => public_path('kop_absen.jpg'),
        ])->setPaper('a4','portrait')->save($fAbs);
        $files[] = $fAbs;

        // =============== 3) Notulensi (opsional) ===============
        $notulensi = DB::table('notulensi')->where('id_rapat',$rapat->id)->first();
        if ($notulensi) {
            $detail = DB::table('notulensi_detail')->where('id_notulensi',$notulensi->id)->orderBy('urut')->get();
            $dokumentasi = DB::table('notulensi_dokumentasi')->where('id_notulensi',$notulensi->id)->get();
            $creator = DB::table('users')->where('id',$notulensi->id_user)->first();

            $jumlah_peserta = $daftar_peserta->count();

            $notulensiApprovals = DB::table('approval_requests as ar')
                ->leftJoin('users as u','u.id','=','ar.approver_user_id')
                ->where('ar.rapat_id', $rapat->id)
                ->where('ar.doc_type', 'notulensi')
                ->orderBy('ar.order_index')
                ->get([
                    'ar.order_index',
                    'ar.status',
                    'ar.signed_at',
                    'ar.rejected_at',
                    'ar.rejection_note',
                    'ar.signature_qr_path',
                    'u.name as approver_name',
                    DB::raw('COALESCE(u.jabatan,"") as approver_jabatan')
                ]);

            $notulensi_approvals = $notulensiApprovals->map(function($r){
                $qr_data = null; $qr_web = null; $qr_fs = null;
                if (!empty($r->signature_qr_path)) {
                    $qr_fs = public_path($r->signature_qr_path);
                    if (is_file($qr_fs)) {
                        $qr_data = 'data:image/png;base64,'.base64_encode(@file_get_contents($qr_fs));
                        $qr_web  = url($r->signature_qr_path);
                    }
                }
                return [
                    'order'     => (int)$r->order_index,
                    'status'    => $r->status,
                    'signed_at' => $r->signed_at,
                    'name'      => $r->approver_name ?: 'Approver',
                    'jabatan'   => $r->approver_jabatan ?: 'Pejabat',
                    'qr_data'   => $qr_data,
                    'qr_web'    => $qr_web,
                    'qr_fs'     => $qr_fs,
                ];
            })->values()->all();

            $notulensi_qr_paths = $notulensiApprovals->pluck('signature_qr_path')->filter()->values()->all();

            $notulis_nama    = $creator->name    ?? ($creator->nama ?? 'Notulis');
            $notulis_jabatan = $creator->jabatan ?? 'Notulis';

            $qr_notulis_data = null;
            if (!empty($notulensi->signature_qr_path) && is_file(public_path($notulensi->signature_qr_path))) {
                $qr_notulis_data = 'data:image/png;base64,'.base64_encode(@file_get_contents(public_path($notulensi->signature_qr_path)));
            }

            $pimpinan_nama  = ($approval1->name ?? 'Approval 1');
            $qr_pimpinan_data = null;
            $pimpinanReq = DB::table('approval_requests')
                ->where('rapat_id', $rapat->id)
                ->where('doc_type', 'notulensi')
                ->where('approver_user_id', $rapat->approval1_user_id)
                ->where('status', 'approved')
                ->orderByDesc('signed_at')
                ->first();
            if ($pimpinanReq && $pimpinanReq->signature_qr_path && is_file(public_path($pimpinanReq->signature_qr_path))) {
                $qr_pimpinan_data = 'data:image/png;base64,'.base64_encode(@file_get_contents(public_path($pimpinanReq->signature_qr_path)));
            }

            $dataNot = [
                'notulensi'            => $notulensi,
                'rapat'                => $rapat,
                'detail'               => $detail,
                'dokumentasi'          => $dokumentasi,
                'creator'              => $creator,
                'jumlah_peserta'       => $jumlah_peserta,

                'notulensi_approvals'  => $notulensi_approvals,
                'notulensi_qr_paths'   => $notulensi_qr_paths,

                'qr_notulis_data'      => $qr_notulis_data,
                'notulis_nama'         => $notulis_nama,
                'notulis_jabatan'      => $notulis_jabatan,
                'qr_pimpinan_data'     => $qr_pimpinan_data,
                'pimpinan_nama'        => $pimpinan_nama,
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

        // Merge & cleanup
        $merger = new \iio\libmergepdf\Merger();
        foreach ($files as $f) $merger->addFile($f);
        $merged = $merger->merge();

        foreach ($files as $f) @unlink($f);

        return $merged;
    }

    public function previewFile(Request $r, $id)
    {
        $row = DB::table('laporan_files')->where('id',$id)->first() ?? abort(404);

        // LIVE preview: render gabungan terbaru
        if ($r->boolean('live') && !empty($row->id_rapat)) {
            $binary = $this->renderGabunganPdfBinary((int)$row->id_rapat);
            return response($binary, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.($row->file_name ?: 'preview.pdf').'"',
                'Cache-Control'       => 'private, max-age=0, no-store',
            ]);
        }

        $abs = storage_path('app/'.$row->file_path);
        if (!is_file($abs)) abort(404, 'File tidak ditemukan.');

        $mime = $row->mime ?: 'application/pdf';
        $headers = [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="'.$row->file_name.'"',
            'Cache-Control'       => 'private, max-age=600',
        ];
        return response()->file($abs, $headers);
    }
}
