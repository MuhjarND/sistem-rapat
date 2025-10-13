<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RapatController extends Controller
{
    // Tampilkan daftar rapat
 public function index(Request $request)
{
    // === Master data ===
    $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

    // === Base query daftar rapat ===
    $query = DB::table('rapat')
        ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
        ->leftJoin('users as pembuat', 'rapat.dibuat_oleh', '=', 'pembuat.id') // pakai kolom "dibuat_oleh"
        ->leftJoin('users as appr1', 'rapat.approval1_user_id', '=', 'appr1.id')
        ->leftJoin('users as appr2', 'rapat.approval2_user_id', '=', 'appr2.id')
        ->select(
            'rapat.*',
            'kategori_rapat.nama as nama_kategori',
            'pembuat.name as nama_pembuat',
            'appr1.name as approval1_nama',
            'appr2.name as approval2_nama'
        );

    // === Filter ===
    if ($request->filled('kategori')) {
        $query->where('rapat.id_kategori', $request->kategori);
    }
    if ($request->filled('tanggal')) {
        $query->whereDate('rapat.tanggal', $request->tanggal);
    }
    if ($request->filled('keyword')) {
        $kw = trim($request->keyword);
        $query->where(function($q) use ($kw) {
            $q->where('rapat.judul', 'like', '%'.$kw.'%')
              ->orWhere('rapat.nomor_undangan', 'like', '%'.$kw.'%')
              ->orWhere('rapat.tempat', 'like', '%'.$kw.'%'); // sekalian cari "Tempat"
        });
    }

    // === Paging ===
    $daftar_rapat = $query
        ->orderBy('tanggal', 'desc')
        ->paginate(6)
        ->appends($request->all());

    // === Hitung status label (Akan Datang/Berlangsung/Selesai/Dibatalkan) ===
    foreach ($daftar_rapat as $rapat) {
        $rapat->status_label = $this->getStatusRapat($rapat);
    }

    // === PRELOAD: Peserta terpilih untuk setiap rapat (agar Select2 preselected di modal Edit) ===
    $rapatIds = $daftar_rapat->pluck('id')->all();
    if (!empty($rapatIds)) {
        // Ambil peserta dari tabel UNDANGAN (sesuaikan nama kolom kalau berbeda)
        $pesertaMap = DB::table('undangan as u')
            ->join('users as usr', 'usr.id', '=', 'u.id_user') // u.id_user = user peserta
            ->whereIn('u.id_rapat', $rapatIds)
            ->select(
                'u.id_rapat',
                'usr.id as id',
                'usr.name',
                'usr.jabatan',
                'usr.unit'
            )
            ->orderBy('usr.name')
            ->get()
            ->groupBy('id_rapat')
            ->map(function ($group) {
                return $group->map(function ($row) {
                    $label = $row->name;
                    if ($row->jabatan) $label .= ' — '.$row->jabatan;
                    if ($row->unit)    $label .= ' · '.$row->unit;
                    return ['id' => (int)$row->id, 'text' => $label];
                })->values()->all();
            });

        // Tempelkan ke setiap item rapat
        foreach ($daftar_rapat as $r) {
            $r->peserta_terpilih = $pesertaMap->get($r->id, []);
        }

        // === PRELOAD: Approval map (undangan & absensi) untuk badge modal "Cek Status" ===
        $apprRows = DB::table('approval_requests as ar')
            ->leftJoin('users as u', 'u.id', '=', 'ar.approver_user_id')
            ->whereIn('ar.rapat_id', $rapatIds)
            ->whereIn('ar.doc_type', ['undangan', 'absensi'])
            ->select(
                'ar.rapat_id',
                'ar.doc_type',
                'ar.order_index',
                'ar.status',
                'ar.signed_at',
                'ar.rejected_at',
                'ar.rejection_note',
                'u.name as approver_name'
            )
            ->orderBy('ar.doc_type')
            ->orderBy('ar.order_index')
            ->get();

        // Bentuk peta: rapat_id => [ 'undangan' => [ {...}, ... ], 'absensi' => [ {...}, ... ] ]
        $approvalMapByRapat = $apprRows
            ->groupBy('rapat_id')
            ->map(function ($rowsPerRapat) {
                return $rowsPerRapat->groupBy('doc_type')->map(function ($groupPerType) {
                    return $groupPerType->map(function ($r) {
                        return [
                            'order'          => (int)$r->order_index,
                            'name'           => $r->approver_name ?: 'Approver',
                            'status'         => $r->status,                     // approved | rejected | pending | blocked
                            'signed_at'      => $r->signed_at,
                            'rejected_at'    => $r->rejected_at,
                            'rejection_note' => $r->rejection_note,
                        ];
                    })->values()->all();
                })->toArray();
            });

        foreach ($daftar_rapat as $r) {
            $r->approval_map = $approvalMapByRapat->get($r->id, []);
        }
    } else {
        // Kalau kosong, tetap set properti agar view aman
        foreach ($daftar_rapat as $r) {
            $r->peserta_terpilih = [];
            $r->approval_map = [];
        }
    }

    // === Master daftar peserta (opsi Select2) ===
    $daftar_peserta = DB::table('users')
        ->where('role', 'peserta')
        ->orderBy('name')
        ->get();

    // === Daftar Approver (approval1 & approval2) ===
    $approval1_list = DB::table('users')
        ->select('id','name','tingkatan')
        ->where('role','approval')
        ->orderBy('name')
        ->get();

    $approval2_list = DB::table('users')
        ->select('id','name','tingkatan')
        ->where('role','approval')
        ->where('tingkatan', 2)
        ->orderBy('name')
        ->get();

    return view('rapat.index', compact(
        'daftar_rapat',
        'daftar_kategori',
        'daftar_peserta',
        'approval1_list',
        'approval2_list'
    ));
}


    // Form tambah rapat (kalau pakai halaman terpisah)
    public function create()
    {
        $daftar_peserta = DB::table('users')->where('role', 'peserta')->get();
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        $approval1_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->orderBy('name')
            ->get();

        $approval2_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->where('tingkatan', 2)
            ->orderBy('name')
            ->get();

        return view('rapat.create', compact('daftar_peserta', 'daftar_kategori', 'approval1_list', 'approval2_list'));
    }

    // Proses simpan rapat & undangan
    public function store(Request $request)
    {
        $request->validate([
            'nomor_undangan'    => 'required|unique:rapat,nomor_undangan',
            'judul'             => 'required',
            'deskripsi'         => 'nullable',
            'tanggal'           => 'required|date',
            'waktu_mulai'       => 'required',
            'tempat'            => 'required',
            'approval1_user_id' => 'required|exists:users,id',
            'approval2_user_id' => 'nullable|exists:users,id',
            'peserta'           => 'required|array|min:1',
            'id_kategori'       => 'required|exists:kategori_rapat,id'
        ]);

        // Simpan rapat
        $id_rapat = DB::table('rapat')->insertGetId([
            'nomor_undangan'    => $request->nomor_undangan,
            'judul'             => $request->judul,
            'deskripsi'         => $request->deskripsi,
            'tanggal'           => $request->tanggal,
            'waktu_mulai'       => $request->waktu_mulai,
            'tempat'            => $request->tempat,
            'dibuat_oleh'       => Auth::id(),
            'id_kategori'       => $request->id_kategori,
            'approval1_user_id' => $request->approval1_user_id,
            'approval2_user_id' => $request->approval2_user_id,
            'token_qr'          => Str::random(32),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // Ambil data rapat & approver utk notifikasi/PDF
        $rapat = DB::table('rapat')->where('id', $id_rapat)->first();
        $approval1 = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
        $approval2 = $rapat->approval2_user_id ? DB::table('users')->where('id', $rapat->approval2_user_id)->first() : null;

        // Insert undangan + kirim notifikasi ke peserta
        foreach ($request->peserta as $id_peserta) {
            DB::table('undangan')->insert([
                'id_rapat'   => $id_rapat,
                'id_user'    => $id_peserta,
                'status'     => 'terkirim',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $peserta = DB::table('users')->where('id', $id_peserta)->first();
        }

        // === Buat antrean approval untuk UNDANGAN (A2 -> A1 bila ada A2 / hanya A1 jika tidak) ===
        $this->createApprovalChainForDoc($id_rapat, 'undangan');
        $this->createApprovalChainForDoc($id_rapat, 'absensi');


        // === Kirim link WA ke approver tahap pertama ===
        $this->notifyFirstApprover($id_rapat, 'undangan', $rapat->judul);

        // === (Opsional) generate PDF pratinjau; final PDF bisa diunduh setelah approve ===
        $daftar_peserta = DB::table('undangan')
            ->join('users','undangan.id_user','=','users.id')
            ->where('undangan.id_rapat', $id_rapat)
            ->select('users.name','users.email','users.jabatan')
            ->get();

        $tampilkan_lampiran = $daftar_peserta->count() > 5;     // lampiran hanya jika > 5
        $tampilkan_daftar_di_surat = !$tampilkan_lampiran;      // daftar di badan surat jika ≤ 5

        // QR mungkin belum ada (belum approved) — tidak masalah
        $qrA1 = DB::table('approval_requests')
            ->where('rapat_id', $id_rapat)
            ->where('doc_type','undangan')
            ->where('approver_user_id', $rapat->approval1_user_id)
            ->where('status','approved')
            ->orderBy('order_index')
            ->value('signature_qr_path');

        $qrA2 = null;
        if (!empty($rapat->approval2_user_id)) {
            $qrA2 = DB::table('approval_requests')
                ->where('rapat_id', $id_rapat)
                ->where('doc_type','undangan')
                ->where('approver_user_id', $rapat->approval2_user_id)
                ->where('status','approved')
                ->orderBy('order_index')
                ->value('signature_qr_path');
        }

        // Catatan: biasanya undangan final diunduh setelah approval selesai.
        // Jika ingin langsung preview:
        // $pdf = Pdf::loadView('rapat.undangan_pdf', [..., 'qrA1'=>$qrA1, 'qrA2'=>$qrA2])->setPaper('A4','portrait');
        // $pdfData = $pdf->output();

        return redirect()->route('rapat.index')->with('success', 'Rapat & Undangan berhasil dibuat. Notifikasi WA sudah dikirim!');
    }

    // Detail rapat
    public function show($id)
    {
        $rapat = DB::table('rapat')
            ->leftJoin('users as a1', 'rapat.approval1_user_id', '=', 'a1.id')
            ->leftJoin('users as a2', 'rapat.approval2_user_id', '=', 'a2.id')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'a1.name as approval1_nama',
                'a2.name as approval2_nama'
            )
            ->where('rapat.id', $id)
            ->first();

        if (!$rapat) abort(404);

        $daftar_peserta = DB::table('undangan')
            ->join('users', 'undangan.id_user', '=', 'users.id')
            ->where('undangan.id_rapat', $id)
            ->select('users.name', 'users.email', 'users.jabatan')
            ->get();

        return view('rapat.show', compact('rapat', 'daftar_peserta'));
    }

    // Form edit rapat
    public function edit($id)
    {
        $rapat = DB::table('rapat')->where('id', $id)->first();
        if (!$rapat) abort(404);

        $daftar_peserta   = DB::table('users')->where('role', 'peserta')->get();
        $daftar_kategori  = DB::table('kategori_rapat')->orderBy('nama')->get();
        $peserta_terpilih = DB::table('undangan')->where('id_rapat', $id)->pluck('id_user')->toArray();

        $approval1_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->orderBy('name')
            ->get();

        $approval2_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->where('tingkatan', 2)
            ->orderBy('name')
            ->get();

        return view('rapat.edit', compact(
            'rapat', 'daftar_peserta', 'peserta_terpilih',
            'daftar_kategori', 'approval1_list', 'approval2_list'
        ));
    }

    // Update rapat & undangan
public function update(Request $request, $id)
{
    $request->validate([
        'baris'                    => 'required|array|min:1',
        'baris.*.hasil_pembahasan' => 'required|string',
        'baris.*.rekomendasi'      => 'nullable|string',
        'baris.*.penanggung_jawab' => 'nullable|string|max:150',
        'baris.*.pj_ids'           => 'nullable|array',
        'baris.*.pj_ids.*'         => 'integer|exists:users,id',
        'baris.*.tgl_penyelesaian' => 'nullable|date',
        // dokumentasi opsional saat update (bisa kosong)
        'dokumentasi'              => 'nullable',
        'dokumentasi.*'            => 'image|max:10240',
    ]);

    // Ambil notulensi + rapat terkait
    $notulensi = DB::table('notulensi')->where('id', $id)->first();
    if (!$notulensi) abort(404);
    $rapatId = (int) $notulensi->id_rapat;

    DB::beginTransaction();
    try {
        // 1) Update header (penanda siapa yang merevisi)
        DB::table('notulensi')->where('id', $id)->update([
            'id_user'    => Auth::id(), // yang merevisi
            'updated_at' => now(),
        ]);

        // 2) Replace detail & tugas (sederhana & aman)
        $detailIds = DB::table('notulensi_detail')
            ->where('id_notulensi', $id)->pluck('id')->all();
        if (!empty($detailIds)) {
            DB::table('notulensi_tugas')->whereIn('id_notulensi_detail', $detailIds)->delete();
        }
        DB::table('notulensi_detail')->where('id_notulensi', $id)->delete();

        $urut = 1;
        foreach ($request->baris as $r) {
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

        // 3) Dokumentasi baru (tambahan; tidak menghapus yang lama)
        if ($request->hasFile('dokumentasi')) {
            $dest = public_path('uploads/notulensi');
            if (!is_dir($dest)) mkdir($dest, 0775, true);

            foreach ($request->file('dokumentasi') as $file) {
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

        // 4) Pastikan QR notulis tetap ada / up-to-date
        $this->ensureNotulensiNotulisQr((int)$rapatId, (int)$id);

        // 5) Pastikan chain approval NOTULENSI ada (jika hilang, buat ulang)
        $rapat = DB::table('rapat')->where('id', $rapatId)->first();
        if ($rapat) {
            // cek apakah ada approval_requests untuk doc_type notulensi
            $existsAny = DB::table('approval_requests')
                ->where('rapat_id', $rapatId)
                ->where('doc_type', 'notulensi')
                ->exists();

            if (!$existsAny) {
                // buat chain seperti di store()
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

        DB::commit();

        // 6) Buka blokir step-step berikutnya (jika sebelumnya REJECT mem-block)
        app(\App\Http\Controllers\ApprovalController::class)
            ->unblockNextSteps((int)$rapatId, 'notulensi', 0);

        // 7) Kirim WA ke approver pertama pending: status "Sudah diperbaiki"
        app(\App\Http\Controllers\ApprovalController::class)
            ->notifyFirstPendingApproverOnResubmission((int)$rapatId, 'notulensi');

        // (Opsional) cap waktu revisi di rapat untuk audit
        if (\Illuminate\Support\Facades\Schema::hasColumn('rapat', 'notulensi_revised_at')) {
            DB::table('rapat')->where('id', $rapatId)->update([
                'notulensi_revised_at' => now(),
                'updated_at'           => now(),
            ]);
        }

        return redirect()->route('notulensi.show', $id)
            ->with('success', 'Perbaikan notulensi disimpan. Dokumen dikembalikan ke antrean approval (status: sudah diperbaiki).');

    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);
        return back()->withErrors('Gagal menyimpan revisi notulensi.')->withInput();
    }
}


    // Hapus rapat & undangan terkait
    public function destroy($id)
    {
        DB::table('undangan')->where('id_rapat', $id)->delete();
        DB::table('approval_requests')->where('rapat_id', $id)->delete(); // bersihkan antrean approval
        DB::table('rapat')->where('id', $id)->delete();
        return redirect()->route('rapat.index')->with('success', 'Rapat berhasil dihapus!');
    }

    // Export undangan PDF (pakai approval + QR)
public function undanganPdf($id)
{
    $rapat = DB::table('rapat')->where('id', $id)->first();
    if (!$rapat) abort(404);

    // Peserta rapat
    $daftar_peserta = DB::table('undangan')
        ->join('users', 'undangan.id_user', '=', 'users.id')
        ->where('undangan.id_rapat', $id)
        ->select('users.name', 'users.email', 'users.jabatan')
        ->get();

    // Approver
    $approval1 = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
    $approval2 = $rapat->approval2_user_id
        ? DB::table('users')->where('id', $rapat->approval2_user_id)->first()
        : null;

    $kop_path = public_path('Screenshot 2025-08-23 121254.jpeg');

    // Aturan tampilan daftar/lampiran
    $tampilkan_lampiran         = $daftar_peserta->count() > 5;
    $tampilkan_daftar_di_surat  = !$tampilkan_lampiran;

    // QR signature approver (terbaru)
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

    // Render PDF
    $pdf = Pdf::loadView('rapat.undangan_pdf', [
        'rapat'                      => $rapat,
        'daftar_peserta'             => $daftar_peserta,
        'approval1'                  => $approval1,
        'approval2'                  => $approval2,
        'kop_path'                   => $kop_path,
        'tampilkan_lampiran'         => $tampilkan_lampiran,
        'tampilkan_daftar_di_surat'  => $tampilkan_daftar_di_surat,
        'qrA1'                       => $qrA1,
        'qrA2'                       => $qrA2,
    ])->setPaper('A4', 'portrait');

    // >>> STREAM INLINE (agar bisa di-preview dalam <object>/<iframe>)
    $filename = 'Undangan-Rapat-' . str_replace(' ', '-', $rapat->judul) . '.pdf';
    $output   = $pdf->output();

    return response($output, 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="'.$filename.'"')
        ->header('X-Frame-Options', 'SAMEORIGIN')     // jangan DENY
        ->header('Cache-Control', 'private, max-age=0, must-revalidate');
}

    private function getStatusRapat($rapat)
    {
        if ($rapat->status === 'dibatalkan') {
            return 'Dibatalkan';
        }

        $now = Carbon::now('Asia/Jayapura');
        $mulai = Carbon::parse($rapat->tanggal . ' ' . $rapat->waktu_mulai, 'Asia/Jayapura');
        $selesai = $mulai->copy()->addHours(2); // default 2 jam

        if ($now->lt($mulai)) {
            return 'Akan Datang';
        } elseif ($now->between($mulai, $selesai)) {
            return 'Berlangsung';
        } elseif ($now->gt($selesai)) {
            return 'Selesai';
        }
        return 'Akan Datang';
    }

    public function batalkan($id)
    {
        DB::table('rapat')->where('id', $id)->update(['status' => 'dibatalkan', 'updated_at' => now()]);
        return redirect()->route('rapat.index')->with('success', 'Rapat berhasil dibatalkan!');
    }

    /**
     * Buat antrean approval untuk dokumen (undangan|absensi|notulensi)
     * Rantai: jika ada A2 => A2 (order 1) -> A1 (order 2); jika tidak, hanya A1 (order 1)
     */
    private function createApprovalChainForDoc($rapatId, $docType)
    {
        $rapat = DB::table('rapat')->where('id',$rapatId)->first();
        if (!$rapat) return;

        $chain = [];
        if (!empty($rapat->approval2_user_id)) {
            $chain[] = ['user_id' => $rapat->approval2_user_id, 'order' => 1];
            $chain[] = ['user_id' => $rapat->approval1_user_id, 'order' => 2];
        } else {
            $chain[] = ['user_id' => $rapat->approval1_user_id, 'order' => 1];
        }

        foreach ($chain as $c) {
            DB::table('approval_requests')->insert([
                'rapat_id'         => $rapatId,
                'doc_type'         => $docType,
                'approver_user_id' => $c['user_id'],
                'order_index'      => $c['order'],
                'status'           => 'pending',
                'sign_token'       => Str::random(48),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    /**
     * Kirim WA ke approver tahap pertama untuk dokumen tertentu
     */
    private function notifyFirstApprover($rapatId, $docType, $judulRapat)
    {
        $firstReq = DB::table('approval_requests')
            ->where('rapat_id',$rapatId)
            ->where('doc_type',$docType)
            ->orderBy('order_index')
            ->first();

        if ($firstReq) {
            $approver = DB::table('users')->where('id',$firstReq->approver_user_id)->first();
            if ($approver && $approver->no_hp) {
                $wa = preg_replace('/^0/', '62', $approver->no_hp);
                $signUrl = url('/approval/sign/'.$firstReq->sign_token); // route ke ApprovalController
                \App\Helpers\FonnteWa::send($wa, "Mohon approval {$docType} rapat: {$judulRapat}\nLink: {$signUrl}");
            }
        }
    }

}
