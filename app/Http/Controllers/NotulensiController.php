<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use iio\libmergepdf\Merger;
use Illuminate\Support\Str;

// Helper Fonnte
use App\Helpers\FonnteWa;
use App\Helpers\TimeHelper;

class NotulensiController extends Controller
{
    public function index()
    {
        return redirect()->route('notulensi.belum');
    }

    protected function baseRapatQuery()
    {
        return DB::table('rapat')
            ->leftJoin('notulensi', 'notulensi.id_rapat', '=', 'rapat.id')
            ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select(
                'rapat.*',
                'notulensi.id as id_notulensi',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            );
    }

    protected function applyFilters($query, Request $request)
    {
        if ($request->filled('kategori')) {
            $query->where('rapat.id_kategori', $request->kategori);
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('rapat.tanggal', $request->tanggal);
        }
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('rapat.judul', 'like', "%{$keyword}%")
                  ->orWhere('rapat.nomor_undangan', 'like', "%{$keyword}%")
                  ->orWhere('rapat.tempat', 'like', "%{$keyword}%");
            });
        }
        return $query;
    }

    public function belum(Request $request)
    {
        $query = $this->baseRapatQuery()->whereNull('notulensi.id');
        $this->applyFilters($query, $request);

        $rapat_belum = $query->orderBy('rapat.tanggal', 'desc')->paginate(6);
        $rapat_belum->appends($request->query());

        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        return view('notulensi.belum', compact('rapat_belum', 'daftar_kategori'));
    }

    public function sudah(Request $request)
    {
        $query = $this->baseRapatQuery()->whereNotNull('notulensi.id');
        $this->applyFilters($query, $request);

        $rapat_sudah = $query->orderBy('rapat.tanggal', 'desc')->paginate(6);
        $rapat_sudah->appends($request->query());

        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        return view('notulensi.sudah', compact('rapat_sudah', 'daftar_kategori'));
    }

    public function create($id_rapat)
    {
        $rapat = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'pimpinan_rapat.nama as nama_pimpinan'
            )
            ->where('rapat.id', $id_rapat)
            ->first();

        if (!$rapat) abort(404);

        if (DB::table('notulensi')->where('id_rapat', $id_rapat)->exists()) {
            return redirect()->route('notulensi.sudah')->with('success', 'Notulensi untuk rapat ini sudah dibuat.');
        }

        $jumlah_peserta = DB::table('undangan')->where('id_rapat', $id_rapat)->count();

        Carbon::setLocale('id');
        $hari_tanggal = Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y');
        $jam = TimeHelper::short($rapat->waktu_mulai);

        // Select2 pakai AJAX (route('users.search'))
        return view('notulensi.create', compact('rapat','jumlah_peserta','hari_tanggal','jam'));
    }

 public function store(Request $request)
{
    $request->validate([
        'id_rapat'                 => 'required|exists:rapat,id',
        'baris'                    => 'required|array|min:1',
        'baris.*.hasil_pembahasan' => 'required|string',
        'baris.*.rekomendasi'      => 'nullable|string',
        'baris.*.penanggung_jawab' => 'nullable|string|max:150',
        'baris.*.pj_ids'           => 'nullable|array',
        'baris.*.pj_ids.*'         => 'integer|exists:users,id',
        'baris.*.tgl_penyelesaian' => 'nullable|date',
        'dokumentasi'              => 'required',
        'dokumentasi.*'            => 'image|max:10240',
    ], [
        'dokumentasi.required' => 'Minimal unggah 3 foto dokumentasi.',
    ]);

    if (DB::table('notulensi')->where('id_rapat', $request->id_rapat)->exists()) {
        return redirect()->route('notulensi.sudah')->with('success', 'Notulensi untuk rapat ini sudah ada.');
    }

    DB::beginTransaction();
    try {
        // Header
        $id_notulensi = DB::table('notulensi')->insertGetId([
            'id_rapat'   => $request->id_rapat,
            'id_user'    => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Detail + tugas
        $urut = 1;
        foreach ($request->baris as $r) {
            $idDetail = DB::table('notulensi_detail')->insertGetId([
                'id_notulensi'     => $id_notulensi,
                'urut'             => $urut++,
                'hasil_pembahasan' => $r['hasil_pembahasan'],
                'rekomendasi'      => $r['rekomendasi'] ?? null,
                'penanggung_jawab' => $r['penanggung_jawab'] ?? null,
                'tgl_penyelesaian' => $r['tgl_penyelesaian'] ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            $pjIds = $r['pj_ids'] ?? [];
            if (is_array($pjIds) && count($pjIds)) {
                $now = now();
                $bulk = [];
                foreach ($pjIds as $uid) {
                    $bulk[] = [
                        'id_notulensi_detail' => $idDetail,
                        'user_id'             => (int)$uid,
                        'tgl_penyelesaian'    => $r['tgl_penyelesaian'] ?? null,
                        'status'              => 'pending',
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                }
                DB::table('notulensi_tugas')->insert($bulk);
            }
        }

        // Dokumentasi
        if ($request->hasFile('dokumentasi')) {
            $dest = public_path('uploads/notulensi');
            if (!is_dir($dest)) @mkdir($dest, 0775, true);

            foreach ($request->file('dokumentasi') as $file) {
                if (!$file || !$file->isValid()) continue;

                $ext      = strtolower($file->getClientOriginalExtension());
                $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $slugBase = preg_replace('/[^a-z0-9\-]+/i', '-', $basename);
                $name     = $slugBase.'-'.uniqid().'.'.$ext;

                $file->move($dest, $name);
                $relPath = 'uploads/notulensi/'.$name;

                DB::table('notulensi_dokumentasi')->insert([
                    'id_notulensi' => $id_notulensi,
                    'file_path'    => $relPath,
                    'caption'      => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        // QR Notulis + chain approval NOTULENSI (order 1 = notulis; berikutnya approver)
        $this->ensureNotulensiNotulisQr((int)$request->id_rapat, (int)$id_notulensi);

        // Tambahkan approver untuk NOTULENSI
        $rapat = DB::table('rapat')->where('id', $request->id_rapat)->first();
        if ($rapat) {
            // Mulai setelah notulis (order 1)
            $order = 2;

            // Approval 2 (jika ada) ditempatkan lebih dulu
            if (!empty($rapat->approval2_user_id)) {
                $exists2 = DB::table('approval_requests')
                    ->where('rapat_id', $rapat->id)
                    ->where('doc_type', 'notulensi')
                    ->where('approver_user_id', $rapat->approval2_user_id)
                    ->exists();

                if (!$exists2) {
                    DB::table('approval_requests')->insert([
                        'rapat_id'         => $rapat->id,
                        'doc_type'         => 'notulensi',
                        'approver_user_id' => $rapat->approval2_user_id,
                        'order_index'      => $order++,
                        'status'           => 'pending',
                        'sign_token'       => Str::random(32),
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                } else {
                    $order++;
                }
            }

            // Approval 1 (selalu terakhir)
            if (!empty($rapat->approval1_user_id)) {
                $exists1 = DB::table('approval_requests')
                    ->where('rapat_id', $rapat->id)
                    ->where('doc_type', 'notulensi')
                    ->where('approver_user_id', $rapat->approval1_user_id)
                    ->exists();

                if (!$exists1) {
                    DB::table('approval_requests')->insert([
                        'rapat_id'         => $rapat->id,
                        'doc_type'         => 'notulensi',
                        'approver_user_id' => $rapat->approval1_user_id,
                        'order_index'      => $order,
                        'status'           => 'pending',
                        'sign_token'       => Str::random(32),
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }
        }

        DB::commit();

        // === KIRIM WA: approver pertama yang masih pending untuk NOTULENSI ===
        // Gunakan helper "satu pintu" di ApprovalController agar robust
        app(\App\Http\Controllers\ApprovalController::class)
            ->notifyFirstPendingApprover((int)$request->id_rapat, 'notulensi');

        return redirect()->route('notulensi.show', $id_notulensi)
            ->with('success', 'Notulensi berhasil dibuat. TTD Notulis dibuat otomatis & approval pimpinan disiapkan. Notifikasi WA sudah dikirim ke approver.');
    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);
        return back()->withErrors('Gagal menyimpan notulensi.')->withInput();
    }
}



    public function show($id)
    {
        $notulensi = DB::table('notulensi')->where('id', $id)->first();
        if (!$notulensi) abort(404);

        $rapat = DB::table('rapat') 
            ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select(
                'rapat.*',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            )
            ->where('rapat.id', $notulensi->id_rapat)
            ->first();

        $detail = DB::table('notulensi_detail')
            ->where('id_notulensi', $id)
            ->orderBy('urut')
            ->get();

        $dokumentasi = DB::table('notulensi_dokumentasi')
            ->where('id_notulensi', $id)
            ->get();

        return view('notulensi.show', compact('notulensi','rapat','detail','dokumentasi'));
    }

    public function edit($id)
    {
        $notulensi = DB::table('notulensi')->where('id', $id)->first();
        if (!$notulensi) abort(404);

        $rapat = DB::table('rapat')
            ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select(
                'rapat.*',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            )
            ->where('rapat.id', $notulensi->id_rapat)
            ->first();

        $detail = DB::table('notulensi_detail')
            ->where('id_notulensi', $id)
            ->orderBy('urut')
            ->get();

        $dokumentasi = DB::table('notulensi_dokumentasi')
            ->where('id_notulensi', $id)
            ->get();

        // Build assignee map: detail_id => [ {id, text}, ... ]
        $assigneesRaw = DB::table('notulensi_tugas')
            ->join('users','users.id','=','notulensi_tugas.user_id')
            ->whereIn('id_notulensi_detail', $detail->pluck('id'))
            ->select(
                'id_notulensi_detail as detail_id',
                'users.id as id',
                'users.name',
                'users.jabatan',
                'users.unit'
            )->get();

        $assigneesMap = $assigneesRaw
            ->groupBy('detail_id')
            ->map(function($group){
                return $group->map(function($u){
                    $label = $u->name;
                    if ($u->jabatan) $label .= ' — '.$u->jabatan;
                    if ($u->unit)    $label .= ' · '.$u->unit;
                    return ['id' => $u->id, 'text' => $label];
                })->values()->all();
            })->toArray();

        return view('notulensi.edit', compact('notulensi','rapat','detail','dokumentasi','assigneesMap'));
    }

public function update(Request $request, $id)
{
    $request->validate([
        'baris'                    => 'nullable|array',
        'baris.*.hasil_pembahasan' => 'required_with:baris|string',
        'baris.*.rekomendasi'      => 'nullable|string',
        'baris.*.penanggung_jawab' => 'nullable|string|max:150',
        'baris.*.pj_ids'           => 'nullable|array',
        'baris.*.pj_ids.*'         => 'integer|exists:users,id',
        'baris.*.tgl_penyelesaian' => 'nullable|date',
        'hapus_dok'                => 'nullable|array',
        'hapus_dok.*'              => 'integer',
        'dokumentasi_baru.*'       => 'nullable|image|max:10240',
    ]);

    DB::beginTransaction();
    try {
        // Sentuh updated_at notulensi (untuk penanda revisi)
        DB::table('notulensi')->where('id', $id)->update(['updated_at' => now()]);

        // ====== DETAIL & TUGAS (replace penuh jika ada input baris) ======
        if ($request->filled('baris')) {
            $oldDetailIds = DB::table('notulensi_detail')->where('id_notulensi', $id)->pluck('id');
            if ($oldDetailIds->count()) {
                DB::table('notulensi_tugas')->whereIn('id_notulensi_detail', $oldDetailIds)->delete();
            }
            DB::table('notulensi_detail')->where('id_notulensi', $id)->delete();

            $urut = 1;
            foreach ($request->baris as $r) {
                if (!isset($r['hasil_pembahasan']) || $r['hasil_pembahasan'] === '') continue;

                $idDetail = DB::table('notulensi_detail')->insertGetId([
                    'id_notulensi'     => $id,
                    'urut'             => $urut++,
                    'hasil_pembahasan' => $r['hasil_pembahasan'],
                    'rekomendasi'      => $r['rekomendasi'] ?? null,
                    'penanggung_jawab' => $r['penanggung_jawab'] ?? null,
                    'tgl_penyelesaian' => $r['tgl_penyelesaian'] ?? null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                $pjIds = $r['pj_ids'] ?? [];
                if (is_array($pjIds) && count($pjIds)) {
                    $bulk = [];
                    foreach ($pjIds as $uid) {
                        $bulk[] = [
                            'id_notulensi_detail' => $idDetail,
                            'user_id'             => (int)$uid,
                            'tgl_penyelesaian'    => $r['tgl_penyelesaian'] ?? null,
                            'status'              => 'pending',
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ];
                    }
                    DB::table('notulensi_tugas')->insert($bulk);
                }
            }
        }

        // ====== HAPUS DOK LAMA TERPILIH ======
        if ($request->filled('hapus_dok')) {
            $hapusIds = $request->hapus_dok;
            $lama = DB::table('notulensi_dokumentasi')->whereIn('id', $hapusIds)->get();
            foreach ($lama as $item) {
                $path = public_path($item->file_path);
                if (is_file($path)) @unlink($path);
            }
            DB::table('notulensi_dokumentasi')->whereIn('id', $hapusIds)->delete();
        }

        // ====== UPLOAD DOK BARU (tambahan) ======
        if ($request->hasFile('dokumentasi_baru')) {
            $dest = public_path('uploads/notulensi');
            if (!is_dir($dest)) mkdir($dest, 0775, true);

            foreach ($request->file('dokumentasi_baru') as $file) {
                if (!$file || !$file->isValid()) continue;

                $ext      = strtolower($file->getClientOriginalExtension());
                $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $slugBase = preg_replace('/[^a-z0-9\-]+/i', '-', $basename);
                $name     = $slugBase.'-'.uniqid().'.'.$ext;

                $file->move($dest, $name);
                $relPath = 'uploads/notulensi/'.$name;

                DB::table('notulensi_dokumentasi')->insert([
                    'id_notulensi' => $id,
                    'file_path'    => $relPath,
                    'caption'      => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        DB::commit();

        // ================== RE-QUEUE APPROVAL (jika sebelumnya REJECT) ==================
        try {
            $rapatId = (int) DB::table('notulensi')->where('id', $id)->value('id_rapat');

            // Pastikan chain NOTULENSI ada; jika hilang, buat minimal sesuai approval1/2
            $rapat = DB::table('rapat')->where('id', $rapatId)->first();
            if ($rapat) {
                $existsAny = DB::table('approval_requests')
                    ->where('rapat_id', $rapatId)
                    ->where('doc_type', 'notulensi')
                    ->exists();
                if (!$existsAny) {
                    $order = 2;
                    if (!empty($rapat->approval2_user_id)) {
                        $exists2 = DB::table('approval_requests')
                            ->where('rapat_id', $rapat->id)
                            ->where('doc_type', 'notulensi')
                            ->where('approver_user_id', $rapat->approval2_user_id)
                            ->exists();
                        if (!$exists2) {
                            DB::table('approval_requests')->insert([
                                'rapat_id'         => $rapat->id,
                                'doc_type'         => 'notulensi',
                                'approver_user_id' => $rapat->approval2_user_id,
                                'order_index'      => $order++,
                                'status'           => 'pending',
                                'sign_token'       => \Illuminate\Support\Str::random(32),
                                'created_at'       => now(),
                                'updated_at'       => now(),
                            ]);
                        } else {
                            $order++;
                        }
                    }
                    if (!empty($rapat->approval1_user_id)) {
                        $exists1 = DB::table('approval_requests')
                            ->where('rapat_id', $rapat->id)
                            ->where('doc_type', 'notulensi')
                            ->where('approver_user_id', $rapat->approval1_user_id)
                            ->exists();
                        if (!$exists1) {
                            DB::table('approval_requests')->insert([
                                'rapat_id'         => $rapat->id,
                                'doc_type'         => 'notulensi',
                                'approver_user_id' => $rapat->approval1_user_id,
                                'order_index'      => $order,
                                'status'           => 'pending',
                                'sign_token'       => \Illuminate\Support\Str::random(32),
                                'created_at'       => now(),
                                'updated_at'       => now(),
                            ]);
                        }
                    }
                }
            }

            // Ambil request NOTULENSI yang terakhir DITOLAK; reset ke pending + flag resubmitted
            $rej = DB::table('approval_requests')
                ->where('rapat_id',  $rapatId)
                ->where('doc_type',  'notulensi')
                ->where('status',    'rejected')
                ->orderByDesc('order_index')
                ->orderByDesc('id')
                ->first();

            if ($rej) {
                $extra = [];
                if (Schema::hasColumn('approval_requests','resubmitted')) {
                    $extra['resubmitted'] = 1;
                }
                if (Schema::hasColumn('approval_requests','resubmitted_at')) {
                    $extra['resubmitted_at'] = now();
                }

                DB::table('approval_requests')->where('id', $rej->id)->update(array_merge([
                    'status'         => 'pending',
                    'sign_token'     => \Illuminate\Support\Str::random(32),
                    'rejection_note' => null,
                    'rejected_at'    => null,
                    'updated_at'     => now(),
                ], $extra));

                // Hapus penanda reject di rapat & buka blokir step setelah urutan penolak
                app(\App\Http\Controllers\ApprovalController::class)
                    ->unblockNextSteps($rapatId, 'notulensi', (int)$rej->order_index);
            } else {
                // Tidak ada row 'rejected' (misal chain sudah dibuat ulang): tetap pastikan unblocked dari awal
                app(\App\Http\Controllers\ApprovalController::class)
                    ->unblockNextSteps($rapatId, 'notulensi', 0);
            }

            // Cap waktu revisi untuk audit (jika kolom ada)
            if (Schema::hasColumn('rapat', 'notulensi_revised_at')) {
                DB::table('rapat')->where('id', $rapatId)->update([
                    'notulensi_revised_at' => now(),
                    'updated_at'           => now(),
                ]);
            }

            // Kirim WA: ke approver pending pertama yang TIDAK TERBLOKIR (pesan "SUDAH DIPERBAIKI")
            app(\App\Http\Controllers\ApprovalController::class)
                ->notifyFirstPendingApproverOnResubmission((int)$rapatId, 'notulensi');

            // (Opsional) pastikan QR notulis tetap ada
            if (method_exists($this, 'ensureNotulensiNotulisQr')) {
                $this->ensureNotulensiNotulisQr((int)$rapatId, (int)$id);
            }
        } catch (\Throwable $e) {
            Log::error('[notulensi-update] requeue/notify fail', ['err' => $e->getMessage()]);
        }
        // ================================================================================

        return redirect()->route('notulensi.show', $id)
            ->with('success', 'Notulensi berhasil diperbarui & dikirim ulang untuk persetujuan (status: sudah diperbaiki).');

    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);
        return back()->withErrors('Gagal memperbarui notulensi.')->withInput();
    }
}



    /** Cetak p1+p2+p3 (dengan QR notulis & pimpinan) */
    public function cetakGabung($id)
    {
        $notulensi = DB::table('notulensi')->where('id', $id)->first() ?? abort(404);

        $rapat = DB::table('rapat')
            ->leftJoin('pimpinan_rapat','rapat.id_pimpinan','=','pimpinan_rapat.id')
            ->leftJoin('kategori_rapat','rapat.id_kategori','=','kategori_rapat.id')
            ->select(
                'rapat.*',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            )
            ->where('rapat.id',$notulensi->id_rapat)
            ->first();

        $detail = DB::table('notulensi_detail')
            ->where('id_notulensi',$id)
            ->orderBy('urut')->get();

        $dokumentasi = DB::table('notulensi_dokumentasi')
            ->where('id_notulensi',$id)
            ->get();

        $creator = DB::table('users')->where('id',$notulensi->id_user)->first();
        $jumlah_peserta = DB::table('undangan')->where('id_rapat', $notulensi->id_rapat)->count();

        // refresh QR Notulis
        $this->ensureNotulensiNotulisQr((int)$notulensi->id_rapat, (int)$notulensi->id, true);

        // QR Notulis (approved)
        $qrNotulis = DB::table('approval_requests')
            ->where('rapat_id', $notulensi->id_rapat)
            ->where('doc_type', 'notulensi')
            ->where('approver_user_id', $notulensi->id_user)
            ->where('status', 'approved')
            ->orderBy('order_index')->first();

        $qr_notulis_data = null;
        if ($qrNotulis && $qrNotulis->signature_qr_path) {
            $fs = public_path($qrNotulis->signature_qr_path);
            if (@is_file($fs)) {
                $qr_notulis_data = 'data:image/png;base64,'.base64_encode(@file_get_contents($fs));
            }
        }

        // QR Pimpinan (approved)
        $qrPimpinan = DB::table('approval_requests')
            ->where('rapat_id', $notulensi->id_rapat)
            ->where('doc_type', 'notulensi')
            ->where('approver_user_id', $rapat->approval1_user_id)
            ->where('status', 'approved')
            ->orderBy('order_index')->first();

        $qr_pimpinan_data = null;
        if ($qrPimpinan && $qrPimpinan->signature_qr_path) {
            $fs = public_path($qrPimpinan->signature_qr_path);
            if (@is_file($fs)) {
                $qr_pimpinan_data = 'data:image/png;base64,'.base64_encode(@file_get_contents($fs));
            }
        }

        $data = compact('notulensi','rapat','detail','dokumentasi','creator','jumlah_peserta');

        $notulis  = $creator;
        $pimpUser = DB::table('users')->where('id', $rapat->approval1_user_id)->first();

        $dataP2 = array_merge($data, [
            'qr_notulis_data'  => $qr_notulis_data,
            'qr_pimpinan_data' => $qr_pimpinan_data,
            'notulis_nama'     => $notulis->name ?? '-',
            'notulis_jabatan'  => $notulis->jabatan ?? 'Notulis',
            'pimpinan_nama'    => $pimpUser->name ?? ($rapat->nama_pimpinan ?? '-'),
            'pimpinan_jabatan' => $pimpUser->jabatan ?? ($rapat->jabatan_pimpinan ?? 'Pimpinan Rapat'),
            'kop'              => public_path('kop_notulen.jpg'),
        ]);

        $tmpDir = storage_path('app');
        $f1 = $tmpDir.'/header-'.Str::random(8).'.pdf';
        $f2 = $tmpDir.'/pembahasan-'.Str::random(8).'.pdf';
        $f3 = $tmpDir.'/dokumentasi-'.Str::random(8).'.pdf';

        Pdf::loadView('notulensi.cetak_p1', $data)
            ->setPaper('a4','portrait')->save($f1);

        Pdf::loadView('notulensi.cetak_p2', $dataP2)
            ->setPaper('a4','landscape')->save($f2);

        Pdf::loadView('notulensi.cetak_p3', $data)
            ->setPaper('a4','portrait')->save($f3);

        $merger = new Merger();
        $merger->addFile($f1);
        $merger->addFile($f2);
        $merger->addFile($f3);
        $mergedPdf = $merger->merge();

        @unlink($f1); @unlink($f2); @unlink($f3);

        $filename = 'Notulensi-'.Str::slug($rapat->judul).'-'.date('d-m-Y', strtotime($notulensi->created_at)).'.pdf';
        return response($mergedPdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }

    /**
     * Buat/refresh QR TTD Notulis (doc_type notulensi, approver=notulis) + tempel logo.
     */
    private function ensureNotulensiNotulisQr(int $rapatId, int $notulenId, bool $forceRefresh = false): void
    {
        $notulensi = DB::table('notulensi')->where('id', $notulenId)->first();
        $rapat     = DB::table('rapat')->where('id', $rapatId)->first();
        if (!$notulensi || !$rapat) return;

        $row = DB::table('approval_requests')
            ->where('rapat_id', $rapatId)
            ->where('doc_type', 'notulensi')
            ->where('approver_user_id', $notulensi->id_user)
            ->first();

        if ($row && $row->status === 'approved' && $row->signature_qr_path && !$forceRefresh) return;

        $notulisUser = DB::table('users')->where('id', $notulensi->id_user)->first();

        $payload = [
            'v'          => 1,
            'doc_type'   => 'notulensi',
            'rapat_id'   => $rapatId,
            'notulen_id' => $notulenId,
            'nomor'      => $rapat->nomor_undangan,
            'judul'      => $rapat->judul,
            'tanggal'    => $rapat->tanggal,
            'approver'   => [
                'id'      => $notulensi->id_user,
                'name'    => $notulisUser->name ?? null,
                'jabatan' => $notulisUser->jabatan ?? null,
                'order'   => 1,
            ],
            'issued_at'  => now()->toIso8601String(),
            'nonce'      => Str::random(18),
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

        $qrContent = route('qr.verify', ['d' => base64_encode($payloadJson)]);
        $encoded   = urlencode($qrContent);
        $qrUrl     = "https://chart.googleapis.com/chart?chs=600x600&cht=qr&chl={$encoded}&chld=H|0";

        $dir = public_path('qr');
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $filename     = 'qr_notulensi_notulis_r'.$rapatId.'_n'.$notulenId.'_'.Str::random(6).'.png';
        $relativePath = 'qr/'.$filename;
        $absolutePath = public_path($relativePath);

        $png = @file_get_contents($qrUrl);
        if ($png === false) {
            $alt = "https://api.qrserver.com/v1/create-qr-code/?size=600x600&data={$encoded}&ecc=H&margin=0";
            $png = @file_get_contents($alt);
        }
        if ($png === false) return;

        $logoPath = public_path('logo_qr.png');
        $saved = false;
        if (function_exists('imagecreatefromstring') && function_exists('imagepng')) {
            $qrImg = @imagecreatefromstring($png);
            if ($qrImg !== false) {
                if (is_file($logoPath)) {
                    $logoImg = @imagecreatefrompng($logoPath);
                    if ($logoImg !== false) {
                        imagealphablending($logoImg, true);
                        imagesavealpha($logoImg, true);

                        $qrW = imagesx($qrImg); $qrH = imagesy($qrImg);
                        $lw  = imagesx($logoImg); $lh = imagesy($logoImg);

                        $targetW = (int) round($qrW * 0.18);
                        $targetH = (int) round($lh * ($targetW / $lw));

                        $dstX = (int) round(($qrW - $targetW) / 2);
                        $dstY = (int) round(($qrH - $targetH) / 2);

                        $logoResized = imagecreatetruecolor($targetW, $targetH);
                        imagealphablending($logoResized, false);
                        imagesavealpha($logoResized, true);
                        imagecopyresampled($logoResized, $logoImg, 0, 0, 0, 0, $targetW, $targetH, $lw, $lh);

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
            file_put_contents($absolutePath, $png);
        }

        if ($row) {
            DB::table('approval_requests')->where('id', $row->id)->update([
                'status'            => 'approved',
                'order_index'       => 1,
                'signature_qr_path' => $relativePath,
                'signature_payload' => $payloadJson,
                'signed_at'         => now(),
                'updated_at'        => now(),
            ]);
        } else {
            DB::table('approval_requests')->insert([
                'rapat_id'          => $rapatId,
                'doc_type'          => 'notulensi',
                'approver_user_id'  => $notulensi->id_user,
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

    public function dashboard()
    {
        $totalRapat     = DB::table('rapat')->count();
        $totalNotulensi = DB::table('notulensi')->count();
        $belumAda       = DB::table('rapat')
                            ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
                            ->whereNull('notulensi.id')
                            ->count();
        $sudahAda       = $totalNotulensi;

        $pending = DB::table('rapat')
            ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
            ->whereNull('notulensi.id')
            ->select('rapat.id','rapat.judul','rapat.tanggal','rapat.waktu_mulai','rapat.tempat')
            ->orderBy('rapat.tanggal','desc')->orderBy('rapat.waktu_mulai','desc')
            ->limit(10)->get();

        $selesai = DB::table('notulensi')
            ->join('rapat','notulensi.id_rapat','=','rapat.id')
            ->select('notulensi.id as id_notulensi','rapat.id as id_rapat','rapat.judul','rapat.tanggal','rapat.waktu_mulai','rapat.tempat')
            ->orderBy('rapat.tanggal','desc')->orderBy('rapat.waktu_mulai','desc')
            ->limit(10)->get();

        $byMonth = DB::table('notulensi')
            ->join('rapat','notulensi.id_rapat','=','rapat.id')
            ->select(DB::raw("DATE_FORMAT(rapat.tanggal,'%Y-%m') as ym"), DB::raw('COUNT(*) as total'))
            ->groupBy('ym')
            ->orderBy('ym','desc')
            ->limit(6)
            ->pluck('total','ym')
            ->reverse();

        return view('notulensi.dashboard', [
            'metrics'  => [
                'totalRapat'     => $totalRapat,
                'totalNotulensi' => $totalNotulensi,
                'belumAda'       => $belumAda,
                'sudahAda'       => $sudahAda,
            ],
            'pending'  => $pending,
            'selesai'  => $selesai,
            'byMonth'  => $byMonth,
        ]);
    }

    /**
     * === Baru ===
     * Kirim notifikasi WA tugas notulensi KE PARA ASSIGNEE,
     * dipanggil HANYA setelah dokumen NOTULENSI dinyatakan FINAL APPROVED.
     */
    public function notifyAssigneesOnNotulensiApproved(int $rapatId): void // === changed (new)
    {
        // Ambil notulensi terkait rapat
        $notulen = DB::table('notulensi')->where('id_rapat', $rapatId)->first();
        if (!$notulen) return;

        // Info rapat ringkas
        $rapat = DB::table('rapat')
            ->where('id', $rapatId)
            ->select('id','judul','tanggal','tempat')
            ->first();
        if (!$rapat) return;

        // Kumpulkan semua assignee + target selesai (per detail)
        $rows = DB::table('notulensi_detail as d')
            ->leftJoin('notulensi_tugas as t', 't.id_notulensi_detail','=','d.id')
            ->leftJoin('users as u','u.id','=','t.user_id')
            ->where('d.id_notulensi', $notulen->id)
            ->whereNotNull('t.user_id')
            ->select(
                'u.id as uid','u.name',
                't.tgl_penyelesaian'
            )->get();

        if ($rows->isEmpty()) return;

        // siapkan nomor telepon
        $phoneExpr = $this->usersPhoneExpr();
        $userIds   = $rows->pluck('uid')->unique()->values()->all();
        $phones    = DB::table('users')
            ->whereIn('id', $userIds)
            ->select('id','name', DB::raw("{$phoneExpr} as phone"))
            ->get()->keyBy('id');

        // URL tugas peserta
        $urlTugas = URL::route('peserta.tugas.index');

        $tglRapat = $rapat->tanggal ? Carbon::parse($rapat->tanggal)->isoFormat('D MMM Y') : '-';

        foreach ($rows as $r) {
            $user = $phones->get($r->uid);
            if (!$user) continue;

            $phoneRaw = $user->phone ?? null;
            $phone    = $this->normalizeMsisdn($phoneRaw);
            if (!$phone) {
                Log::warning('WA tugas skip (no phone)', ['user_id'=>$r->uid, 'name'=>$user->name ?? null]);
                continue;
            }

            $tglSelesai = $r->tgl_penyelesaian ? Carbon::parse($r->tgl_penyelesaian)->isoFormat('D MMM Y') : '-';

            // pesan (boleh disesuaikan ke versi formal bila diinginkan)
            $msg = $this->buildTaskAssignedMessage(
                $user->name ?? '-',
                $rapat->judul ?? '-',
                $tglRapat,
                $rapat->tempat ?? '-',
                $tglSelesai,
                $urlTugas
            );

            $res = FonnteWa::send($phone, $msg);
            if (!($res['status'] ?? false) && !($res['ok'] ?? false)) {
                Log::error('Gagal kirim WA tugas notulensi (approved)', [
                    'user_id'=>$r->uid, 'phone'=>$phone, 'result'=>$res
                ]);
            }
        }
    }

    /**
     * Template pesan WA untuk penugasan notulensi.
     */
    private function buildTaskAssignedMessage(string $nama, string $judulRapat, string $tglRapat, string $tempat, string $tglSelesai, string $urlTugas): string
    {
        return "Yth. Bapak/Ibu {$nama},\n\n"
            ."Dengan hormat, Bapak/Ibu mendapatkan *penugasan tindak lanjut notulensi* dengan detail berikut:\n\n"
            ."*Rapat*          : {$judulRapat}\n"
            ."*Tanggal*        : {$tglRapat}\n"
            ."*Tempat*         : {$tempat}\n"
            ."*Target selesai* : {$tglSelesai}\n\n"
            ."Silakan meninjau detail dan memperbarui status tugas melalui tautan berikut:\n{$urlTugas}\n\n"
            ."Atas perhatian dan kerja samanya, kami ucapkan terima kasih.";
    }

    /**
     * Ekspresi COALESCE nomor HP yang benar-benar ada di tabel users.
     * Contoh hasil: "COALESCE(users.`no_hp`, users.`wa`)"
     */
    private function usersPhoneExpr(): string
    {
        $candidates = ['no_hp','phone','telp','telepon','hp','whatsapp','wa','no_telp','no_wa'];
        $available  = [];

        foreach ($candidates as $col) {
            if (Schema::hasColumn('users', $col)) {
                $available[] = "users.`{$col}`";
            }
        }

        if (empty($available)) {
            return "NULL";
        }

        return 'COALESCE('.implode(', ', $available).')';
    }

    /**
     * Normalisasi MSISDN ke format internasional ID: 62xxxxxxxxxx
     */
    private function normalizeMsisdn(?string $num): ?string
    {
        if (!$num) return null;
        $n = preg_replace('/[^0-9+]/', '', $num);

        // +62xxxxxxxx -> 62xxxxxxxx
        if (strpos($n, '+62') === 0) $n = '62'.substr($n, 3);

        // 0xxxxxxxx -> 62xxxxxxxx
        if (strpos($n, '0') === 0) $n = '62'.substr($n, 1);

        // 8xxxxxxxx -> 62xxxxxxxx
        if (strpos($n, '8') === 0) $n = '62'.$n;

        // validasi sederhana
        if (!preg_match('/^62[0-9]{8,15}$/', $n)) return null;

        return $n;
    }
}

