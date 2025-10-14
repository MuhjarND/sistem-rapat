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
        'nomor_undangan'    => 'required|unique:rapat,nomor_undangan,' . $id,
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

    DB::beginTransaction();
    try {
        // ===== 1) Update data rapat (UNDANGAN)
        DB::table('rapat')->where('id', $id)->update([
            'nomor_undangan'    => $request->nomor_undangan,
            'judul'             => $request->judul,
            'deskripsi'         => $request->deskripsi,
            'tanggal'           => $request->tanggal,
            'waktu_mulai'       => $request->waktu_mulai,
            'tempat'            => $request->tempat,
            'id_kategori'       => $request->id_kategori,
            'approval1_user_id' => $request->approval1_user_id,
            'approval2_user_id' => $request->approval2_user_id,
            'updated_at'        => now(),
        ]);

        // ===== 2) Reset & simpan ulang undangan peserta
        DB::table('undangan')->where('id_rapat', $id)->delete();
        foreach ($request->peserta as $id_peserta) {
            DB::table('undangan')->insert([
                'id_rapat'   => $id,
                'id_user'    => $id_peserta,
                'status'     => 'terkirim',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ===== 3) Sinkronisasi chain approval UNDANGAN dengan approver terbaru
        // Urutan yang dipakai: approval2 -> approval1 (order_index 1 lalu 2)
        $desired = [];
        if (!empty($request->approval2_user_id)) $desired[1] = (int)$request->approval2_user_id;
        if (!empty($request->approval1_user_id)) $desired[2] = (int)$request->approval1_user_id;

        // Ambil semua row approval_requests untuk doc_type 'undangan' rapat ini
        $existing = DB::table('approval_requests')
            ->where('rapat_id', $id)
            ->where('doc_type', 'undangan')
            ->orderBy('order_index')
            ->get();

        // Petakan yang sudah approved agar tidak diutak-atik
        $approvedByOrder = $existing->where('status','approved')->keyBy('order_index');
        $nonApproved     = $existing->where('status','!=','approved');

        // a) Hapus non-approved yang tidak ada lagi di daftar desired (approver berubah)
        foreach ($nonApproved as $row) {
            if (!isset($desired[$row->order_index]) || (int)$desired[$row->order_index] !== (int)$row->approver_user_id) {
                DB::table('approval_requests')->where('id', $row->id)->delete();
            }
        }

        // b) Pastikan tiap desired ada row-nya (kecuali jika sudah approved oleh orang tsb)
        foreach ($desired as $ord => $uid) {
            $existsOrd = DB::table('approval_requests')
                ->where('rapat_id', $id)
                ->where('doc_type', 'undangan')
                ->where('order_index', $ord)
                ->first();

            if ($existsOrd) {
                // kalau sudah ada tapi approver berbeda & belum approved, ganti ke user baru
                if ($existsOrd->status !== 'approved' && (int)$existsOrd->approver_user_id !== (int)$uid) {
                    DB::table('approval_requests')->where('id',$existsOrd->id)->update([
                        'approver_user_id' => $uid,
                        'status'           => 'pending',
                        'sign_token'       => \Illuminate\Support\Str::random(32),
                        'updated_at'       => now(),
                    ]);
                }
            } else {
                // belum ada: buat baru (pending)
                DB::table('approval_requests')->insert([
                    'rapat_id'         => $id,
                    'doc_type'         => 'undangan',
                    'approver_user_id' => $uid,
                    'order_index'      => $ord,
                    'status'           => 'pending',
                    'sign_token'       => \Illuminate\Support\Str::random(32),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }

        // ===== 4) Jika sebelumnya ada REJECT, reset step yang ditolak → PENDING + token baru,
        //           tandai "resubmitted", dan buka blokir step berikutnya
        $rejected = DB::table('approval_requests')
            ->where('rapat_id', $id)
            ->where('doc_type', 'undangan')
            ->where('status', 'rejected')
            ->orderBy('order_index','asc')
            ->first();

        if ($rejected) {
            $extra = [];
            if (\Schema::hasColumn('approval_requests','resubmitted')) {
                $extra['resubmitted'] = 1;
            }
            if (\Schema::hasColumn('approval_requests','resubmitted_at')) {
                $extra['resubmitted_at'] = now();
            }

            DB::table('approval_requests')->where('id', $rejected->id)->update(array_merge([
                'status'         => 'pending',
                'rejection_note' => null,
                'rejected_at'    => null,
                'sign_token'     => \Illuminate\Support\Str::random(32),
                'updated_at'     => now(),
            ], $extra));

            // buka step setelahnya yang tadinya diblokir
            DB::table('approval_requests')
                ->where('rapat_id', $id)
                ->where('doc_type', 'undangan')
                ->where('order_index', '>', $rejected->order_index)
                ->where('status', 'blocked')
                ->update([
                    'status'     => 'pending',
                    'updated_at' => now(),
                ]);
        } else {
            // Tidak ada 'rejected': amankan semua yang blocked → pending
            DB::table('approval_requests')
                ->where('rapat_id', $id)
                ->where('doc_type', 'undangan')
                ->where('status', 'blocked')
                ->update(['status' => 'pending', 'updated_at' => now()]);
        }

        // ===== 5) Cap waktu revisi (jika kolom ada); JANGAN null-kan undangan_rejected_at
        if (\Schema::hasColumn('rapat','undangan_revised_at')) {
            DB::table('rapat')->where('id',$id)->update([
                'undangan_revised_at' => now(),
                'updated_at'          => now(),
            ]);
        } else {
            // minimal update cap utama
            DB::table('rapat')->where('id',$id)->update(['updated_at'=>now()]);
        }

        DB::commit();

        // ===== 6) Kirim WA dgn pesan "SUDAH DIPERBAIKI" ke approver pertama yang pending & tidak terblokir
        app(\App\Http\Controllers\ApprovalController::class)
            ->notifyFirstPendingApproverOnResubmission((int)$id, 'undangan');

        return redirect()->route('rapat.index')->with('success', 'Undangan berhasil diperbarui & dikirim ulang ke antrean approval (status: sudah diperbaiki).');

    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);
        return back()->withErrors('Gagal memperbarui undangan.')->withInput();
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
