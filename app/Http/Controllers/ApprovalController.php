<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route as RouteFacade;
use App\Helpers\FonnteWa;

class ApprovalController extends Controller
{
    /**
     * Dashboard ringkas approval user.
     */
    public function dashboard()
    {
        $userId = auth()->id();
        $today  = now()->toDateString();

        // Pending milik user
        $pending = DB::table('approval_requests as ar')
            ->join('rapat as r','ar.rapat_id','=','r.id')
            ->select(
                'ar.id','ar.doc_type','ar.order_index','ar.status','ar.sign_token',
                'ar.rapat_id',
                'r.judul','r.nomor_undangan','r.tanggal','r.waktu_mulai','r.tempat'
            )
            ->where('ar.approver_user_id',$userId)
            ->where('ar.status','pending')
            ->orderBy('r.tanggal')
            ->orderBy('ar.order_index')
            ->get();

        // Tandai blocked
        $pending = $pending->map(function($row){
            $row->blocked = DB::table('approval_requests')
                ->where('rapat_id',$row->rapat_id)
                ->where('doc_type',$row->doc_type)
                ->where('order_index','<',$row->order_index)
                ->where('status','!=','approved')
                ->exists();
            return $row;
        });

        $pendingOpen    = $pending->where('blocked', false)->values();
        $pendingBlocked = $pending->where('blocked', true)->values();

        // Ringkasan per doc_type (pending)
        $byType = DB::table('approval_requests as ar')
            ->select('ar.doc_type', DB::raw('COUNT(*) as total'))
            ->where('ar.approver_user_id',$userId)
            ->where('ar.status','pending')
            ->groupBy('ar.doc_type')
            ->pluck('total','doc_type');

        // Riwayat approved 30 hari
        $recentApproved = DB::table('approval_requests as ar')
            ->join('rapat as r','ar.rapat_id','=','r.id')
            ->select('ar.doc_type','ar.order_index','ar.signed_at','r.judul','r.tanggal','r.tempat')
            ->where('ar.approver_user_id',$userId)
            ->where('ar.status','approved')
            ->where('ar.signed_at','>=', now()->subDays(30))
            ->orderBy('ar.signed_at','desc')
            ->limit(12)
            ->get();

        // ====== METRIK BARU ======
        $docsApprovedTotal = DB::table('approval_requests')
            ->where('approver_user_id',$userId)
            ->where('status','approved')
            ->count();

        $rapatDiikuti = DB::table('undangan as u')
            ->join('rapat as r','r.id','=','u.id_rapat')
            ->where('u.id_user', $userId)
            ->count();

        // pecahan persentase untuk progress stacked
        $pPending = max(1, $pending->count()); // hindari /0
        $pendingByTypePct = [
            'undangan'  => (int) round(100 * (int)($byType['undangan']  ?? 0) / $pPending),
            'notulensi' => (int) round(100 * (int)($byType['notulensi'] ?? 0) / $pPending),
            'absensi'   => (int) round(100 * (int)($byType['absensi']   ?? 0) / $pPending),
        ];

        $metrics = [
            'pending_total'  => $pending->count(),
            'open_total'     => $pendingOpen->count(),
            'blocked_total'  => $pendingBlocked->count(),
            'approved_30d'   => $recentApproved->count(),
            'today_to_sign'  => $pendingOpen->where('tanggal', '>=', $today)->count(),
            'by_type'        => [
                'undangan'  => (int) ($byType['undangan']  ?? 0),
                'notulensi' => (int) ($byType['notulensi'] ?? 0),
                'absensi'   => (int) ($byType['absensi']   ?? 0),
            ],
            'docs_approved_total' => $docsApprovedTotal,
            'meetings_joined'     => $rapatDiikuti,
            'pending_pct'         => $pendingByTypePct,
        ];

        return view('approval.dashboard', compact('pendingOpen','pendingBlocked','recentApproved','metrics'));
    }

    /**
     * List pending milik user (paginated) + flag "resubmitted" untuk notulensi.
     */
public function pending(Request $request)
{
    $userId = auth()->id();

    // Deteksi kolom audit yang mungkin ada di tabel rapat
    $hasNotuRejected = \Illuminate\Support\Facades\Schema::hasColumn('rapat','notulensi_rejected_at');
    $hasUndRejected  = \Illuminate\Support\Facades\Schema::hasColumn('rapat','undangan_rejected_at');
    $hasUndRevised   = \Illuminate\Support\Facades\Schema::hasColumn('rapat','undangan_revised_at');

    // Ekspresi raw untuk dipakai di CASE
    $notuRejCol = $hasNotuRejected ? 'r.notulensi_rejected_at' : 'NULL';
    $undRejCol  = $hasUndRejected  ? 'r.undangan_rejected_at'  : 'NULL';
    // Jika tidak ada undangan_revised_at, fallback ke r.updated_at sebagai indikator revisi
    $undRevCol  = $hasUndRevised   ? 'r.undangan_revised_at'   : 'r.updated_at';

    $rows = DB::table('approval_requests as ar')
        ->join('rapat as r', 'ar.rapat_id', '=', 'r.id')
        // Notulensi untuk membaca updated_at notulensi & hitung detail/dok
        ->leftJoin('notulensi as n', function($j){
            $j->on('n.id_rapat','=','r.id');
        })
        ->select(
            'ar.id','ar.doc_type','ar.order_index','ar.status','ar.sign_token',
            'ar.rapat_id',
            'r.judul','r.nomor_undangan','r.tanggal','r.waktu_mulai','r.tempat',

            // (1) BLOCKED dihitung langsung (hindari N+1)
            DB::raw("EXISTS(
                SELECT 1 FROM approval_requests ar2
                 WHERE ar2.rapat_id = ar.rapat_id
                   AND ar2.doc_type  = ar.doc_type
                   AND ar2.order_index < ar.order_index
                   AND ar2.status <> 'approved'
            ) as blocked"),

            // (2a) Resubmitted: NOTULENSI (n.updated_at > notulensi_rejected_at)
            DB::raw("CASE 
                WHEN ar.doc_type = 'notulensi'
                 AND ".($hasNotuRejected ? "{$notuRejCol} IS NOT NULL" : "0")."
                 AND n.updated_at IS NOT NULL
                 ".($hasNotuRejected ? "AND n.updated_at > {$notuRejCol}" : "")."
                THEN 1 ELSE 0 END as resub_notulensi"),

            // (2b) Resubmitted: UNDANGAN (undangan_revised_at > undangan_rejected_at) 
            // fallback: r.updated_at > undangan_rejected_at kalau kolom revised tidak ada
            DB::raw("CASE
                WHEN ar.doc_type = 'undangan'
                 ".($hasUndRejected ? "AND {$undRejCol} IS NOT NULL" : "")."
                 ".($hasUndRejected 
                        ? "AND {$undRevCol} IS NOT NULL AND {$undRevCol} > {$undRejCol}"
                        : "AND 0")."
                THEN 1 ELSE 0 END as resub_undangan"),

            // (3) last_fix_at: notulensi pakai n.updated_at; undangan pakai undangan_revised_at (atau updated_at)
            DB::raw("CASE 
                WHEN ar.doc_type='notulensi' AND n.updated_at IS NOT NULL THEN n.updated_at
                WHEN ar.doc_type='undangan'  THEN {$undRevCol}
                ELSE NULL END as last_fix_at"),

            // (4a) revised_items: untuk NOTULENSI = jumlah baris detail yang diperbarui setelah ditolak
            DB::raw("CASE 
                WHEN ar.doc_type='notulensi' ".($hasNotuRejected ? "AND {$notuRejCol} IS NOT NULL" : "")."
                THEN (
                    SELECT COUNT(*) FROM notulensi_detail d
                      WHERE d.id_notulensi = n.id
                      ".($hasNotuRejected ? "AND d.updated_at > {$notuRejCol}" : "")."
                )
                ELSE 0 END as revised_items"),

            // (4b) revised_items_und: untuk UNDANGAN = jumlah baris undangan (peserta) yang diperbarui setelah ditolak
            DB::raw("CASE 
                WHEN ar.doc_type='undangan' ".($hasUndRejected ? "AND {$undRejCol} IS NOT NULL" : "")."
                THEN (
                    SELECT COUNT(*) FROM undangan u
                      WHERE u.id_rapat = r.id
                      ".($hasUndRejected ? "AND u.updated_at > {$undRejCol}" : "")."
                )
                ELSE 0 END as revised_items_und"),

            // (5) revised_docs: untuk NOTULENSI = jumlah dokumentasi yang diperbarui setelah ditolak; undangan => 0 (tidak ada tabel lampiran khusus)
            DB::raw("CASE 
                WHEN ar.doc_type='notulensi' ".($hasNotuRejected ? "AND {$notuRejCol} IS NOT NULL" : "")."
                THEN (
                    SELECT COUNT(*) FROM notulensi_dokumentasi nd
                      WHERE nd.id_notulensi = n.id
                      ".($hasNotuRejected ? "AND nd.updated_at > {$notuRejCol}" : "")."
                )
                ELSE 0 END as revised_docs")
        )
        ->where('ar.approver_user_id', $userId)
        ->where('ar.status', 'pending')
        ->orderBy('r.tanggal')       // urut per tanggal rapat
        ->orderBy('ar.order_index')  // lalu urutan step
        ->paginate(8)
        ->appends($request->query());

    // Lengkapi kolom turunan untuk view (preview_url & resubmitted unify)
    $rows->getCollection()->transform(function($r){
        // Preview URL
        $preview = null;
        if ($r->doc_type === 'notulensi') {
            $nid = DB::table('notulensi')->where('id_rapat',$r->rapat_id)->value('id');
            if ($nid && \Illuminate\Support\Facades\Route::has('notulensi.cetak')) {
                $preview = route('notulensi.cetak', $nid);
            }
        } elseif ($r->doc_type === 'undangan') {
            if (\Illuminate\Support\Facades\Route::has('rapat.undangan.pdf')) {
                $preview = route('rapat.undangan.pdf', $r->rapat_id);
            }
        } elseif ($r->doc_type === 'absensi') {
            if (\Illuminate\Support\Facades\Route::has('absensi.preview')) {
                $preview = route('absensi.preview', $r->rapat_id);
            } elseif (\Illuminate\Support\Facades\Route::has('absensi.export.pdf')) {
                $preview = route('absensi.export.pdf', $r->rapat_id).'?preview=1';
            }
        }
        $r->preview_url = $preview;

        // Satukan flag resubmitted untuk konsumsi view:
        $r->resubmitted = (int)($r->resub_notulensi ?? 0) === 1 || (int)($r->resub_undangan ?? 0) === 1;

        // Satukan ringkasan "berapa yang berubah" ke satu angka untuk ditampilkan (opsional)
        // - notulensi: revised_items + revised_docs
        // - undangan : revised_items_und
        if ($r->doc_type === 'notulensi') {
            $r->revised_total = (int)($r->revised_items ?? 0) + (int)($r->revised_docs ?? 0);
        } elseif ($r->doc_type === 'undangan') {
            $r->revised_total = (int)($r->revised_items_und ?? 0);
        } else {
            $r->revised_total = 0;
        }

        return $r;
    });

    return view('approval.pending', compact('rows'));
}


    /**
     * Halaman konfirmasi approval via token.
     */
    public function signForm($token)
    {
        $req = DB::table('approval_requests as ar')
            ->leftJoin('rapat as r', 'ar.rapat_id', '=', 'r.id')
            ->leftJoin('users as u', 'ar.approver_user_id', '=', 'u.id')
            ->leftJoin('kategori_rapat as k', 'r.id_kategori', '=', 'k.id')
            ->select(
                'ar.*',
                'r.judul', 'r.nomor_undangan', 'r.tanggal', 'r.waktu_mulai', 'r.tempat',
                'u.name as approver_name',
                'k.nama as nama_kategori'
            )
            ->where('ar.sign_token', $token)
            ->first();

        if (!$req) abort(404);

        $blocked = DB::table('approval_requests')
            ->where('rapat_id', $req->rapat_id)
            ->where('doc_type', $req->doc_type)
            ->where('order_index', '<', $req->order_index)
            ->where('status', '!=', 'approved')
            ->exists();

        // Preview URL
        $previewUrl = null;
        if ($req->doc_type === 'notulensi') {
            $notulenId = DB::table('notulensi')->where('id_rapat', $req->rapat_id)->value('id');
            if ($notulenId && RouteFacade::has('notulensi.cetak')) {
                $previewUrl = route('notulensi.cetak', $notulenId);
            }
        } elseif ($req->doc_type === 'undangan') {
            if (RouteFacade::has('rapat.undangan.pdf')) {
                $previewUrl = route('rapat.undangan.pdf', $req->rapat_id);
            }
        } elseif ($req->doc_type === 'absensi') {
            if (RouteFacade::has('absensi.export.pdf')) {
                $previewUrl = route('absensi.export.pdf', $req->rapat_id). '?preview=1';
            }
        }

        // Data tambahan
        $detail = collect();
        $jumlah_peserta = DB::table('undangan')->where('id_rapat', $req->rapat_id)->count();

        if ($req->doc_type === 'notulensi') {
            $notulenId = DB::table('notulensi')->where('id_rapat', $req->rapat_id)->value('id');
            if ($notulenId) {
                $raw = DB::table('notulensi_detail as d')
                    ->leftJoin('notulensi_tugas as t', 't.id_notulensi_detail', '=', 'd.id')
                    ->leftJoin('users as us', 'us.id', '=', 't.user_id')
                    ->select(
                        'd.id','d.urut','d.hasil_pembahasan','d.rekomendasi','d.penanggung_jawab','d.tgl_penyelesaian',
                        DB::raw("GROUP_CONCAT(us.name ORDER BY us.name SEPARATOR ', ') as pj_names")
                    )
                    ->where('d.id_notulensi', $notulenId)
                    ->groupBy('d.id','d.urut','d.hasil_pembahasan','d.rekomendasi','d.penanggung_jawab','d.tgl_penyelesaian')
                    ->orderBy('d.urut')
                    ->get();

                $detail = $raw->map(function($r){
                    $pieces = [];
                    if (!empty($r->pj_names)) $pieces[] = $r->pj_names;
                    if (!empty($r->penanggung_jawab)) $pieces[] = $r->penanggung_jawab;
                    $r->pj_text = count($pieces) ? implode(' — ', $pieces) : null;
                    return $r;
                });
            }
        }

        return view('approval.sign', compact('req','blocked','previewUrl','detail','jumlah_peserta'));
    }

    /**
     * Proses approval/reject.
     */
public function signSubmit(Request $request, $token) 
{
    // 1) Ambil request approval
    $req = DB::table('approval_requests')->where('sign_token', $token)->first();
    if (!$req) abort(404);

    // Sudah approved: langsung done
    if ($req->status === 'approved') {
        return redirect()->route('approval.done', ['token' => $token])
            ->with('success', 'Dokumen ini sudah disetujui sebelumnya.');
    }

    // 2) Cek step sebelumnya
    $blocked = DB::table('approval_requests')
        ->where('rapat_id', $req->rapat_id)
        ->where('doc_type', $req->doc_type)
        ->where('order_index', '<', $req->order_index)
        ->where('status', '!=', 'approved')
        ->exists();
    if ($blocked) {
        return back()->with('error', 'Tahap sebelum Anda belum selesai.');
    }

    // 3) Aksi
    $action = $request->input('action', 'approve'); // approve | reject

    // === REJECT ===
    if ($action === 'reject') {
        $note = trim((string) $request->input('rejection_note', ''));

        // 3a) Tandai request ini REJECTED
        DB::table('approval_requests')->where('id', $req->id)->update([
            'status'         => 'rejected',
            'rejection_note' => $note ?: null,
            'rejected_at'    => now(),
            'updated_at'     => now(),
        ]);

        // 3b) Isi rapat.<doc_type>_rejected_at bila ada kolomnya
        $rejectCol = $req->doc_type . '_rejected_at';
        if (Schema::hasColumn('rapat', $rejectCol)) {
            DB::table('rapat')->where('id', $req->rapat_id)->update([$rejectCol => now()]);
        }

        // 3c) AUTO-BLOCK semua step berikutnya (pending → blocked)
        DB::table('approval_requests')
            ->where('rapat_id', $req->rapat_id)
            ->where('doc_type', $req->doc_type)
            ->where('order_index', '>', $req->order_index)
            ->where('status', 'pending')
            ->update([
                'status'     => 'blocked',
                'updated_at' => now(),
            ]);

        // 3d) Notifikasi ke pembuat
        $rapat = DB::table('rapat')->where('id', $req->rapat_id)->first();
        $this->notifyCreatorOnReject($req->doc_type, $rapat, $note, $req);

        return redirect()->route('approval.done', ['token' => $token])
            ->with('success', 'Status ditolak telah direkam. Tahap berikutnya diblokir & pemberitahuan dikirim.');
    }

    // === APPROVE ===
    $rapat    = DB::table('rapat')->where('id', $req->rapat_id)->first();
    $approver = DB::table('users')->where('id', $req->approver_user_id)->first();

    $payload = [
        'v'         => 1,
        'doc_type'  => $req->doc_type,
        'rapat_id'  => $req->rapat_id,
        'nomor'     => $rapat->nomor_undangan ?? null,
        'judul'     => $rapat->judul,
        'tanggal'   => $rapat->tanggal,
        'approver'  => [
            'id'      => $approver->id,
            'name'    => $approver->name,
            'jabatan' => $approver->jabatan ?? null,
            'order'   => $req->order_index,
        ],
        'issued_at' => now()->toIso8601String(),
        'nonce'     => Str::random(16),
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

    // 4) Generate QR
    $qrContent = route('qr.verify', ['d' => base64_encode($payloadJson)]);

    $qrDir = public_path('qr');
    if (!is_dir($qrDir)) @mkdir($qrDir, 0755, true);

    $basename     = 'qr_' . $req->doc_type . '_r' . $req->rapat_id . '_a' . $approver->id . '_' . Str::random(6) . '.png';
    $relativePath = 'qr/' . $basename;
    $absolutePath = public_path($relativePath);

    $ok = $this->saveQrWithLogo($qrContent, $absolutePath, 600, public_path('logo_qr.png'));
    if (!$ok) {
        return back()->with('error', 'Gagal membuat QR (akses internet/allow_url_fopen nonaktif).');
    }

    // 5) Simpan signature
    DB::table('approval_requests')->where('id', $req->id)->update([
        'status'             => 'approved',
        'signature_qr_path'  => $relativePath,
        'signature_payload'  => $payloadJson,
        'signed_at'          => now(),
        'updated_at'         => now(),
    ]);

    // 6) Lanjutkan ke approver berikutnya / selesai
    $next = DB::table('approval_requests')
        ->where('rapat_id', $req->rapat_id)
        ->where('doc_type', $req->doc_type)
        ->where('order_index', '>', $req->order_index)
        ->orderBy('order_index')
        ->first();

    if ($next) {
        // Kirim WA ke approver berikutnya (tetap)
        $nextApprover = DB::table('users')->where('id', $next->approver_user_id)->first();
        if ($nextApprover && $nextApprover->no_hp) {
            $wa = preg_replace('/^0/', '62', $nextApprover->no_hp);
            $signUrl = url('/approval/sign/'.$next->sign_token);
            \App\Helpers\FonnteWa::send($wa,
                "Assalamu’alaikum Wr. Wb.\n\n".
                "Dengan Hormat,\n".
                "Mohon kesediaan untuk memberikan *Approval* pada dokumen *{$req->doc_type}* rapat berikut:\n\n".
                "📄 *{$rapat->judul}*\n\n".
                "Silakan lakukan tinjau dan setujui melalui tautan di bawah ini:\n{$signUrl}\n\n".
                "Terima kasih.\n".
                "Wassalamu’alaikum Wr. Wb."
            );
        }
    } else {
        // step terakhir: tandai selesai
        $col = $req->doc_type . '_approved_at';
        if (Schema::hasColumn('rapat', $col)) {
            DB::table('rapat')->where('id', $req->rapat_id)->update([$col => now()]);
        }

        // UNDANGAN final-approved => buat QR absensi & kirim WA peserta
        if ($req->doc_type === 'undangan') {
            $this->generateAbsensiQr((int)$req->rapat_id);
            app(\App\Http\Controllers\AbsensiController::class)
                ->ensureAbsensiQrMirrorsUndangan((int) $req->rapat_id);

            $this->notifyParticipantsOnInvitationApproved($rapat);
        }

        // NOTULENSI final-approved => kirim WA tugas ke assignee notulen
        if ($req->doc_type === 'notulensi') {
            app(\App\Http\Controllers\NotulensiController::class)
                ->notifyAssigneesOnNotulensiApproved((int)$req->rapat_id);
        }

        // 🔔 Tambahan generik untuk SEMUA doc_type:
        // Kirim WA ke pembuat dokumen bahwa semua approval sudah selesai
        $this->notifyCreatorIfDocFullyApproved(
            (int)$req->rapat_id,
            (string)$req->doc_type,
            $approver->name ?? null
        );
    }

    return redirect()->route('approval.done', ['token' => $token])
        ->with('success', 'Approval berhasil & QR dibuat (dengan logo).');
}


    /**
     * Halaman selesai approval.
     */
    public function done($token)
    {
        $req = DB::table('approval_requests')->where('sign_token', $token)->first();
        if (!$req) abort(404);

        $rapat = DB::table('rapat')->where('id', $req->rapat_id)->first();
        return view('approval.done', compact('req', 'rapat'));
    }

    public function tasks(Request $request)
    {
        $status   = $request->get('status');
        $q        = trim($request->get('q', ''));
        $rapatId  = $request->get('rapat');
        $userId   = $request->get('user');
        $eviden   = $request->get('eviden');
        $perPage  = max(5, (int) ($request->get('per_page', 15)));

        $base = DB::table('notulensi_tugas as t')
            ->join('notulensi_detail as d', 'd.id', '=', 't.id_notulensi_detail')
            ->join('notulensi as n', 'n.id', '=', 'd.id_notulensi')
            ->join('rapat as r', 'r.id', '=', 'n.id_rapat')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id');

        $summaryQuery = clone $base;
        $summary = [
            'total'   => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->where('t.status', 'pending')->count(),
            'proses'  => (clone $summaryQuery)->whereIn('t.status', ['proses','in_progress'])->count(),
            'done'    => (clone $summaryQuery)->where('t.status', 'done')->count(),
            'overdue' => (clone $summaryQuery)
                ->whereIn('t.status', ['pending','proses','in_progress'])
                ->whereNotNull('d.tgl_penyelesaian')
                ->whereDate('d.tgl_penyelesaian', '<', now()->toDateString())
                ->count(),
        ];

        $list = (clone $base)
            ->select(
                't.id',
                't.status',
                't.eviden_path',
                't.eviden_link',
                't.eviden_note',
                't.updated_at',
                't.eviden_note',
                'd.hasil_pembahasan',
                'd.rekomendasi',
                'd.tgl_penyelesaian',
                'r.id as rapat_id',
                'r.judul as rapat_judul',
                'r.tanggal as rapat_tanggal',
                'r.waktu_mulai',
                'r.tempat',
                'u.name as peserta_nama',
                'u.unit as peserta_unit'
            );

        if ($status === 'proses') {
            $list->whereIn('t.status', ['proses','in_progress']);
        } elseif (!empty($status)) {
            $list->where('t.status', $status);
        }

        if ($q !== '') {
            $list->where(function($w) use ($q) {
                $w->where('d.hasil_pembahasan','like',"%{$q}%")
                  ->orWhere('d.rekomendasi','like',"%{$q}%")
                  ->orWhere('r.judul','like',"%{$q}%")
                  ->orWhere('u.name','like',"%{$q}%");
            });
        }

        if ($rapatId) {
            $list->where('r.id', $rapatId);
        }

        if ($userId) {
            $list->where('u.id', $userId);
        }

        if ($eviden === 'ada') {
            $list->where(function($w){
                $w->whereNotNull('t.eviden_path')
                  ->orWhereNotNull('t.eviden_link');
            });
        } elseif ($eviden === 'belum') {
            $list->whereNull('t.eviden_path')
                 ->whereNull('t.eviden_link');
        }

        $tasks = $list
            ->orderByRaw("CASE WHEN t.status!='done' THEN 0 ELSE 1 END ASC")
            ->orderByRaw('COALESCE(d.tgl_penyelesaian, r.tanggal) ASC')
            ->paginate($perPage)
            ->appends($request->query());

        return view('approval.tugas', [
            'tasks'   => $tasks,
            'summary' => $summary,
            'filter'  => [
                'status' => $status,
                'q'      => $q,
                'rapat'  => $rapatId,
                'user'   => $userId,
                'eviden' => $eviden,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function remindTask(Request $request, $id)
    {
        $task = DB::table('notulensi_tugas as t')
            ->join('notulensi_detail as d', 'd.id', '=', 't.id_notulensi_detail')
            ->join('notulensi as n', 'n.id', '=', 'd.id_notulensi')
            ->join('rapat as r', 'r.id', '=', 'n.id_rapat')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->select(
                't.id','t.status','t.user_id',
                'd.hasil_pembahasan','d.tgl_penyelesaian',
                'r.judul','r.tanggal','r.tempat',
                'u.name as peserta_nama'
            )
            ->where('t.id', $id)
            ->first();

        if (!$task) {
            return back()->withErrors('Tugas tidak ditemukan.');
        }

        if (in_array($task->status, ['done'])) {
            return back()->with('info', 'Tugas sudah selesai, tidak perlu mengirim pengingat.');
        }

        $phone = $this->extractPhoneNumberByUserId($task->user_id);
        if (!$phone) {
            return back()->withErrors('Nomor WhatsApp peserta tidak ditemukan.');
        }

        $deadline = $task->tgl_penyelesaian ? \Carbon\Carbon::parse($task->tgl_penyelesaian)->isoFormat('D MMM Y') : 'tidak ditentukan';
        $tglRapat = $task->tanggal ? \Carbon\Carbon::parse($task->tanggal)->isoFormat('D MMM Y') : '-';

        $cleanUraian = trim(strip_tags($task->hasil_pembahasan));
        $message =
            "*Pengingat Tugas Notulensi*\n\n".
            "Yth. *{$task->peserta_nama}*,\n".
            "Anda memiliki tugas notulensi dari rapat *{$task->judul}* ({$tglRapat} · {$task->tempat}).\n\n".
            "Uraian: {$cleanUraian}\n".
            "Target selesai: *{$deadline}*\n\n".
            "Mohon segera menindaklanjuti dan mengunggah eviden bila sudah dikerjakan. Terima kasih.";

        try {
            $response = FonnteWa::send($phone, $message);
            $ok = (bool) ($response['status'] ?? $response['ok'] ?? false);
        } catch (\Throwable $e) {
            Log::error('Gagal kirim WA tugas reminder', ['task_id'=>$task->id,'err'=>$e->getMessage()]);
            $ok = false;
        }

        return $ok
            ? back()->with('success', 'Pengingat WA telah dikirim ke peserta.')
            : back()->withErrors('Gagal mengirim pengingat WA. Silakan coba lagi.');
    }

    /**
     * Generate QR ABSENSI (dipanggil otomatis saat undangan selesai).
     */
    private function generateAbsensiQr(int $rapatId): void
    {
        $rapat = DB::table('rapat')->where('id', $rapatId)->first();
        if (!$rapat) return;

        $approvers = [];
        if (!empty($rapat->approval1_user_id)) $approvers[] = (int)$rapat->approval1_user_id;
        if (!empty($rapat->approval2_user_id)) $approvers[] = (int)$rapat->approval2_user_id;

        $secret = config('app.key');
        if (is_string($secret) && Str::startsWith($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        foreach ($approvers as $idx => $uid) {
            $user = DB::table('users')->where('id', $uid)->first();
            if (!$user) continue;

            $payload = [
                'v'        => 1,
                'doc_type' => 'absensi',
                'rapat_id' => $rapat->id,
                'nomor'    => $rapat->nomor_undangan ?? null,
                'judul'    => $rapat->judul,
                'tanggal'  => $rapat->tanggal,
                'approver' => [
                    'id'      => $user->id,
                    'name'    => $user->name,
                    'jabatan' => $user->jabatan ?? null,
                    'order'   => $idx + 1,
                ],
                'issued_at'=> now()->toIso8601String(),
                'nonce'    => Str::random(16),
            ];
            $payload['sig'] = hash_hmac(
                'sha256',
                json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                $secret
            );
            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

            $qrContent = route('qr.verify', ['d' => base64_encode($payloadJson)]);

            $qrDir = public_path('qr');
            if (!is_dir($qrDir)) @mkdir($qrDir, 0755, true);

            $basename     = 'qr_absensi_r'.$rapat->id.'_a'.$user->id.'_'.Str::random(6).'.png';
            $relativePath = 'qr/'.$basename;
            $absolutePath = public_path($relativePath);

            $ok = $this->saveQrWithLogo($qrContent, $absolutePath, 600, public_path('logo_qr.png'));
            if (!$ok) {
                Log::warning('[absensi-qr] gagal membuat QR: rapat '.$rapat->id.' user '.$user->id);
                continue;
            }

            $exists = DB::table('approval_requests')
                ->where('rapat_id', $rapat->id)
                ->where('doc_type', 'absensi')
                ->where('approver_user_id', $user->id)
                ->where('status', 'approved')
                ->exists();

            if (!$exists) {
                DB::table('approval_requests')->insert([
                    'rapat_id'          => $rapat->id,
                    'doc_type'          => 'absensi',
                    'approver_user_id'  => $user->id,
                    'order_index'       => $idx + 1,
                    'status'            => 'approved',
                    'sign_token'        => Str::random(32),
                    'signature_qr_path' => $relativePath,
                    'signature_payload' => $payloadJson,
                    'signed_at'         => now(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            } else {
                DB::table('approval_requests')
                    ->where('rapat_id', $rapat->id)
                    ->where('doc_type', 'absensi')
                    ->where('approver_user_id', $user->id)
                    ->update([
                        'signature_qr_path' => $relativePath,
                        'signature_payload' => $payloadJson,
                        'signed_at'         => now(),
                        'updated_at'        => now(),
                    ]);
            }
        }
    }

    /**
     * Simpan QR + logo (opsional).
     */
    private function saveQrWithLogo(string $qrContent, string $absolutePath, int $sizePx = 600, string $logoPath = null): bool
    {
        $encoded = urlencode($qrContent);
        $qrUrl   = "https://chart.googleapis.com/chart?chs={$sizePx}x{$sizePx}&cht=qr&chl={$encoded}&chld=H|0";

        $png = @file_get_contents($qrUrl);
        if ($png === false) {
            $qrUrl2 = "https://api.qrserver.com/v1/create-qr-code/?size={$sizePx}x{$sizePx}&data={$encoded}&ecc=H&margin=0";
            $png = @file_get_contents($qrUrl2);
            if ($png === false) return false;
        }

        $saved = false;

        if (function_exists('imagecreatefromstring') && function_exists('imagepng')) {
            $qrImg = @imagecreatefromstring($png);
            if ($qrImg !== false) {
                if ($logoPath && is_file($logoPath)) {
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
            @file_put_contents($absolutePath, $png);
            $saved = true;
        }

        return $saved;
    }

    /**
     * Kirim pemberitahuan ke pembuat dokumen ketika REJECT.
     */
    private function notifyCreatorOnReject(string $docType, $rapat, ?string $note, $reqRow): void
    {
        if (!$rapat) return;

        $creatorId = null;
        $editUrl   = null;

        if ($docType === 'notulensi') {
            // creator ambil dari tabel notulensi
            $notulen = DB::table('notulensi')->where('id_rapat', $rapat->id)->first();
            if ($notulen) {
                $creatorId = (int) $notulen->id_user;
                $editUrl   = route('notulensi.edit', $notulen->id);
            }
            if (!$editUrl) {
                $editUrl = route('notulensi.belum');
            }

        } elseif ($docType === 'undangan') {
            // dukung kolom 'dibuat_oleh' selain created_by/id_user
            if (Schema::hasColumn('rapat', 'dibuat_oleh') && !empty($rapat->dibuat_oleh)) {
                $creatorId = (int) $rapat->dibuat_oleh;
            } elseif (Schema::hasColumn('rapat', 'created_by') && !empty($rapat->created_by)) {
                $creatorId = (int) $rapat->created_by;
            } elseif (Schema::hasColumn('rapat', 'id_user') && !empty($rapat->id_user)) {
                $creatorId = (int) $rapat->id_user;
            }

            try { $editUrl = route('rapat.edit', $rapat->id); }
            catch (\Throwable $e) { $editUrl = route('rapat.index'); }

        } elseif ($docType === 'absensi') {
            if (Schema::hasColumn('rapat', 'dibuat_oleh') && !empty($rapat->dibuat_oleh)) {
                $creatorId = (int) $rapat->dibuat_oleh;
            } elseif (Schema::hasColumn('rapat', 'created_by') && !empty($rapat->created_by)) {
                $creatorId = (int) $rapat->created_by;
            } elseif (Schema::hasColumn('rapat', 'id_user') && !empty($rapat->id_user)) {
                $creatorId = (int) $rapat->id_user;
            }

            $editUrl = route('absensi.index');
        }

        if (!$creatorId) return;

        $creator = DB::table('users')->where('id', $creatorId)->first();
        if (!$creator) return;

        $judul = $rapat->judul ?? '-';
        $jenis = ucfirst($docType);
        $catat = $note ? "\nCatatan: ".$note : '';
        $link  = $editUrl ?: url('/');

        $approverName = $reqRow->approver_user_id
            ? (DB::table('users')->where('id', $reqRow->approver_user_id)->value('name') ?: 'Approver')
            : 'Approver';

        $pesan = "Dokumen *{$jenis}* untuk rapat:\n"
            ."• *{$judul}*\n"
            ."Status: *DITOLAK*\n"
            ."Oleh: {$approverName} pada ".now()->format('d/m/Y H:i')."{$catat}\n\n"
            ."Silakan tindak lanjuti di:\n{$link}";

        // ambil nomor WA dari kolom yang tersedia
        $phone = null;
        if (property_exists($creator, 'phone') && $creator->phone) {
            $phone = \App\Helpers\FonnteWa::normalizeNumber($creator->phone);
        } elseif (property_exists($creator, 'no_hp') && $creator->no_hp) {
            $phone = \App\Helpers\FonnteWa::normalizeNumber($creator->no_hp);
        }
        if ($phone) {
            \App\Helpers\FonnteWa::send($phone, $pesan);
        }
    }

    /**
     * Buka blokir step-step berikutnya setelah kreator mengirim revisi.
     * Catatan: TIDAK mengosongkan rapat.<doc_type>_rejected_at agar jejak penolakan tetap ada.
     */
    public function unblockNextSteps(int $rapatId, string $docType, int $fromOrderIndex = 0): void
    {
        // (SENGAJA dihapus) Reset rejected_at — biarkan tetap ada sebagai jejak waktu untuk deteksi "Sudah diperbaiki".
        /*
        $col = $docType . '_rejected_at';
        if (Schema::hasColumn('rapat', $col)) {
            DB::table('rapat')->where('id', $rapatId)->update([$col => null]);
        }
        */

        // Ubah semua step setelah $fromOrderIndex yang statusnya 'blocked' → 'pending'
        DB::table('approval_requests')
            ->where('rapat_id', $rapatId)
            ->where('doc_type', $docType)
            ->where('order_index', '>', $fromOrderIndex)
            ->where('status', 'blocked')
            ->update([
                'status'     => 'pending',
                'updated_at' => now(),
            ]);
    }

    // ============================================================
    // [NEW] Kirim WhatsApp ke peserta setelah UNDANGAN final approved
    // ============================================================
    private function notifyParticipantsOnInvitationApproved($rapat): void
    {
        if (!$rapat) return;

        // Cegah kirim ganda jika sudah ada timestamp
        if (\Illuminate\Support\Facades\Schema::hasColumn('rapat', 'undangan_notified_at') && !empty($rapat->undangan_notified_at)) {
            return;
        }

        // Deteksi kolom yang tersedia di tabel undangan
        $hasUserId = \Illuminate\Support\Facades\Schema::hasColumn('undangan', 'user_id');
        $hasIdUser = \Illuminate\Support\Facades\Schema::hasColumn('undangan', 'id_user');
        $hasNoHp   = \Illuminate\Support\Facades\Schema::hasColumn('undangan', 'no_hp');
        $hasNama   = \Illuminate\Support\Facades\Schema::hasColumn('undangan', 'nama');

        $peserta = collect();

        if ($hasUserId || $hasIdUser) {
            $joinCol = $hasUserId ? 'user_id' : 'id_user';
            $peserta = DB::table('undangan as u')
                ->leftJoin('users as usr', 'usr.id', '=', "u.$joinCol")
                ->where('u.id_rapat', $rapat->id)
                ->select('usr.name', 'usr.no_hp')
                ->get();
        }

        if ($peserta->isEmpty() && $hasNoHp) {
            $selects = ['u.no_hp'];
            if ($hasNama) $selects[] = 'u.nama';

            $peserta = DB::table('undangan as u')
                ->where('u.id_rapat', $rapat->id)
                ->select($selects)
                ->get()
                ->map(function ($r) {
                    return (object)[
                        'name'  => property_exists($r, 'nama') ? $r->nama : null,
                        'no_hp' => $r->no_hp ?? null,
                    ];
                });
        }

        if ($peserta->isEmpty()) return;

        // ===== Informasi Rapat =====
        $judul   = $rapat->judul ?? '-';
        $nomor   = $rapat->nomor_undangan ?? '-';
        $tanggal = $rapat->tanggal ? \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') : '-';
        $waktu   = $rapat->waktu_mulai ?? '-';
        $tempat  = $rapat->tempat ?? '-';

        // ===== Link PDF dan Preview Peserta =====
        $pdfLink = null;
        try {
            if (\Illuminate\Support\Facades\Route::has('rapat.undangan.pdf')) {
                $pdfLink = route('rapat.undangan.pdf', $rapat->id);
            }
        } catch (\Throwable $e) {
            $pdfLink = null;
        }

        $previewLink = null
        ;
        try {
            if (\Illuminate\Support\Facades\Route::has('peserta.rapat.show')) {
                $previewLink = route('peserta.rapat.show', $rapat->id);
            }
        } catch (\Throwable $e) {
            $previewLink = null;
        }

        // ===== Pesan Formal =====
        $pesan =
            "Assalamu’alaikum warahmatullahi wabarakatuh,\n\n"
          . "Yth. Bapak/Ibu Peserta Rapat,\n\n"
          . "Dengan hormat, kami sampaikan bahwa undangan rapat berikut:\n\n"
          . "• Nomor: *{$nomor}*\n"
          . "• Judul: *{$judul}*\n"
          . "• Hari/Tanggal: *{$tanggal}*\n"
          . "• Waktu: *{$waktu} WIT*\n"
          . "• Tempat: *{$tempat}*\n\n"
          . "Silakan meninjau detail rapat melalui tautan berikut:\n"
          . ($previewLink ? "🔗 *Preview Rapat:* {$previewLink}\n" : "")
          . ($pdfLink ? "📄 *Undangan (PDF):* {$pdfLink}\n" : "")
          . "\nAtas perhatian dan kehadirannya kami ucapkan terima kasih.\n\n"
          . "Wassalamu’alaikum warahmatullahi wabarakatuh.\n\n";

        // ===== Kirim WA =====
        $sent = 0;
        $sentTo = [];

        foreach ($peserta as $row) {
            if (empty($row->no_hp)) continue;
            $phone = \App\Helpers\FonnteWa::normalizeNumber($row->no_hp);
            if (!$phone || isset($sentTo[$phone])) continue;

            \App\Helpers\FonnteWa::send($phone, $pesan);
            $sentTo[$phone] = true;
            $sent++;
        }

        // Tandai sudah kirim WA (jika kolom tersedia)
        if ($sent > 0 && \Illuminate\Support\Facades\Schema::hasColumn('rapat', 'undangan_notified_at')) {
            DB::table('rapat')->where('id', $rapat->id)->update([
                'undangan_notified_at' => now(),
                'updated_at'           => now(),
            ]);
        }
    }

    /**
     * Kirim WA ke approver pertama yang statusnya 'pending' untuk $docType pada rapat $rapatId.
     * Dipakai saat NOTULENSI baru dibuat agar approver segera menandatangani.
     */
public function notifyNextApproverDocReady(int $rapatId, string $docType = 'notulensi'): void
{
    // cari step pending terawal
    $firstPending = DB::table('approval_requests as ar')
        ->where('ar.rapat_id', $rapatId)
        ->where('ar.doc_type', $docType)
        ->where('ar.status', 'pending')
        ->orderBy('ar.order_index', 'asc')
        ->first();

    if (!$firstPending) return;

    $approver = DB::table('users')->where('id', $firstPending->approver_user_id)->first();
    if (!$approver) return;

    $rapat = DB::table('rapat')
        ->select('id','judul','tanggal','tempat',
            // kolom audit optional
            'undangan_rejected_at','undangan_revised_at',
            'notulensi_rejected_at'
        )
        ->where('id', $rapatId)->first();
    if (!$rapat) return;

    // Nomor HP
    $phone = null;
    if (\Illuminate\Support\Facades\Schema::hasColumn('users','no_hp') && !empty($approver->no_hp)) {
        $phone = $approver->no_hp;
    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('users','phone') && !empty($approver->phone)) {
        $phone = $approver->phone;
    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('users','wa') && !empty($approver->wa)) {
        $phone = $approver->wa;
    }
    if (!$phone) return;

    // normalisasi
    if (method_exists(\App\Helpers\FonnteWa::class, 'normalizeNumber')) {
        $msisdn = \App\Helpers\FonnteWa::normalizeNumber($phone);
    } else {
        $n = preg_replace('/[^0-9+]/','',$phone);
        if (strpos($n,'+62')===0) $n = '62'.substr($n,3);
        if (strpos($n,'0')===0)   $n = '62'.substr($n,1);
        if (strpos($n,'8')===0)   $n = '62'.$n;
        $msisdn = preg_match('/^62[0-9]{8,15}$/',$n) ? $n : null;
    }
    if (!$msisdn) return;

    // Label jenis dokumen
    $label = strtoupper($docType);

    // Deteksi apakah ini revisi setelah penolakan
    $isResub = false;
    if ($docType === 'undangan' && \Illuminate\Support\Facades\Schema::hasColumn('rapat','undangan_rejected_at')) {
        if (!empty($rapat->undangan_revised_at) && !empty($rapat->undangan_rejected_at)) {
            $isResub = \Carbon\Carbon::parse($rapat->undangan_revised_at)->gt(\Carbon\Carbon::parse($rapat->undangan_rejected_at));
        }
    } elseif ($docType === 'notulensi' && \Illuminate\Support\Facades\Schema::hasColumn('rapat','notulensi_rejected_at')) {
        // opsional kalau kamu juga set revised_at notulensi (di notulensi@update)
        // bisa gunakan updated_at notulensi sebagai pembanding bila diperlukan
        // $isResub = true/false;
    }

    $signUrl = url('/approval/sign/'.$firstPending->sign_token);
    $tgl     = $rapat->tanggal ? \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('D MMM Y') : '-';

    $msg = "Assalamu’alaikum Wr. Wb.\n\n"
         . "Dengan Hormat, \nMohon *persetujuan {$label}* untuk rapat:\n"
         . "• *{$rapat->judul}*\n"
         . "• Tanggal: {$tgl}\n"
         . "• Tempat: {$rapat->tempat}\n\n"
         . "Silakan tinjau & setujui pada tautan berikut:\n{$signUrl}\n";

    if ($isResub) {
        $msg .= "\n*Dokumen ini telah diperbaiki setelah penolakan sebelumnya.*\n";
    }

    $msg .= "\nTerima kasih.\nWassalamu’alaikum Wr. Wb.";

    \App\Helpers\FonnteWa::send($msisdn, $msg);
}

// app/Http/Controllers/ApprovalController.php

public function notifyFirstPendingApprover(int $rapatId, string $docType): void
{
    // Ambil request pertama yang pending
    $req = DB::table('approval_requests')
        ->where('rapat_id', $rapatId)
        ->where('doc_type', $docType)
        ->orderBy('order_index')
        ->first();

    if (!$req) { Log::warning("[WA] No approval_requests for {$docType} rapat={$rapatId}"); return; }

    // Jika step pertama sudah bukan pending (mis. approved), cari pending berikutnya
    if (!in_array($req->status, ['pending','blocked'])) {
        $req = DB::table('approval_requests')
            ->where('rapat_id', $rapatId)
            ->where('doc_type', $docType)
            ->where('status', 'pending')
            ->orderBy('order_index')
            ->first();
        if (!$req) { Log::info("[WA] No PENDING approval for {$docType} rapat={$rapatId}"); return; }
    }

    $approver = DB::table('users')->where('id', $req->approver_user_id)->first();
    if (!$approver || empty($approver->no_hp)) {
        Log::warning("[WA] Approver missing phone for {$docType} rapat={$rapatId} approver_id={$req->approver_user_id}");
        return;
    }

    $rapat = DB::table('rapat')->where('id', $rapatId)->first();
    $judul = $rapat->judul ?? ucfirst($docType);

    $wa = preg_replace('/\D+/', '', (string)$approver->no_hp);      // keep digits
    $wa = preg_replace('/^0/', '62', $wa);                          // 08xx -> 62xx
    $signUrl = url('/approval/sign/'.$req->sign_token);
    $docTitle = strtoupper($docType);

    $msg = "Assalamu’alaikum Wr. Wb.\n\nDengan hormat,\nMohon kesediaan Bapak/Ibu untuk memberikan *Approval* pada dokumen *{$docTitle}* rapat:\n\n📄 *{$judul}*\n\nSilakan tinjau & setujui melalui tautan berikut:\n{$signUrl}\n\nTerima kasih.\nWassalamu’alaikum Wr. Wb.";

    try {
        \App\Helpers\FonnteWa::send($wa, $msg);
        Log::info("[WA] sent {$docType} to {$wa} (rapat={$rapatId}, req={$req->id})");
    } catch (\Throwable $e) {
        Log::error("[WA] failed {$docType} to {$wa} (rapat={$rapatId}, req={$req->id}): ".$e->getMessage());
    }
}


    /**
     * [NEW] Kirim WA ke approver pertama pending setelah dokumen (notulensi) diperbaiki.
     */
public function notifyFirstPendingApproverOnResubmission(int $rapatId, string $docType = 'notulensi'): void
{
    // 0) Validasi docType
    $docType = strtolower($docType);
    if (!in_array($docType, ['undangan','notulensi','absensi'], true)) return;

    // 1) Ambil rapatnya (judul/tanggal/tempat dipakai di pesan)
    $rapat = DB::table('rapat')
        ->select('id','judul','nomor_undangan','tanggal','waktu_mulai','tempat')
        ->where('id', $rapatId)->first();
    if (!$rapat) return;

    // 2) Cari approver pending *terawal* yang TIDAK terblokir
    //    NOT EXISTS memastikan tidak ada step sebelumnya yang belum approved.
    $firstPending = DB::table('approval_requests as ar')
        ->where('ar.rapat_id', $rapatId)
        ->where('ar.doc_type', $docType)
        ->where('ar.status', 'pending')
        ->whereRaw("NOT EXISTS (
            SELECT 1 FROM approval_requests ar2
             WHERE ar2.rapat_id = ar.rapat_id
               AND ar2.doc_type  = ar.doc_type
               AND ar2.order_index < ar.order_index
               AND ar2.status <> 'approved'
        )")
        ->orderBy('ar.order_index','asc')
        ->first();

    if (!$firstPending) {
        // Tidak ada approver pending yang “siap ditandatangani” ⇒ tidak usah kirim WA
        return;
    }

    // 3) Ambil profil approver + nomor WA
    $approver = DB::table('users')->where('id', $firstPending->approver_user_id)->first();
    if (!$approver) return;

    // Cari nomor WA dari kolom yang tersedia
    $phone = null;
    if (\Illuminate\Support\Facades\Schema::hasColumn('users','no_hp') && !empty($approver->no_hp)) {
        $phone = $approver->no_hp;
    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('users','phone') && !empty($approver->phone)) {
        $phone = $approver->phone;
    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('users','wa') && !empty($approver->wa)) {
        $phone = $approver->wa;
    }
    if (!$phone) return;

    // Normalisasi msisdn (gunakan helper jika ada)
    if (method_exists(\App\Helpers\FonnteWa::class, 'normalizeNumber')) {
        $msisdn = \App\Helpers\FonnteWa::normalizeNumber($phone);
    } else {
        $n = preg_replace('/[^0-9+]/','',$phone);
        if (strpos($n,'+62')===0) $n = '62'.substr($n,3);
        if (strpos($n,'0')===0)   $n = '62'.substr($n,1);
        if (strpos($n,'8')===0)   $n = '62'.$n;
        $msisdn = preg_match('/^62[0-9]{8,15}$/',$n) ? $n : null;
    }
    if (!$msisdn) return;

    // 4) Siapkan pesan “Sudah diperbaiki”
    $tgl  = $rapat->tanggal ? \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('D MMM Y') : '-';
    $sign = url('/approval/sign/'.$firstPending->sign_token);

    // Label dokumen
    $label = strtoupper($docType); // UNDANGAN / NOTULENSI / ABSENSI

    // Versi pesan per jenis
    if ($docType === 'undangan') {
        $nomor = $rapat->nomor_undangan ?: '-';
        $msg =
            "Assalamu’alaikum Wr. Wb.\n\n"
          . "Mohon *persetujuan {$label}* (status: *SUDAH DIPERBAIKI*) untuk rapat berikut:\n"
          . "• Nomor: *{$nomor}*\n"
          . "• Judul: *{$rapat->judul}*\n"
          . "• Tanggal: {$tgl}\n"
          . "• Tempat: {$rapat->tempat}\n\n"
          . "Silakan tinjau & setujui pada tautan berikut:\n{$sign}\n\n"
          . "Terima kasih.\nWassalamu’alaikum Wr. Wb.";
    } elseif ($docType === 'notulensi') {
        $msg =
            "Assalamu’alaikum Wr. Wb.\n\n"
          . "Mohon *persetujuan {$label}* (status: *SUDAH DIPERBAIKI*) untuk rapat:\n"
          . "• *{$rapat->judul}*\n"
          . "• Tanggal: {$tgl}\n"
          . "• Tempat: {$rapat->tempat}\n\n"
          . "Silakan tinjau & setujui pada tautan berikut:\n{$sign}\n\n"
          . "Terima kasih.\nWassalamu’alaikum Wr. Wb.";
    } else { // absensi (jika kamu pakai flow revisi absensi)
        $msg =
            "Assalamu’alaikum Wr. Wb.\n\n"
          . "Mohon *persetujuan {$label}* (status: *SUDAH DIPERBAIKI*) untuk rapat:\n"
          . "• *{$rapat->judul}*\n"
          . "• Tanggal: {$tgl}\n"
          . "• Tempat: {$rapat->tempat}\n\n"
          . "Silakan tinjau & setujui pada tautan berikut:\n{$sign}\n\n"
          . "Terima kasih.\nWassalamu’alaikum Wr. Wb.";
    }

    // 5) Kirim WA
    \App\Helpers\FonnteWa::send($msisdn, $msg);
}

    // ===== Halaman dokumen yang telah disetujui (dengan filter + dedupe) =====
    public function approved(Request $request)
    {
        $uid = auth()->id();

        // ====== filters ======
        $kategori = $request->input('kategori');
        $tanggal  = $request->input('tanggal');
        $keyword  = $request->input('keyword');
        $docType  = $request->input('doc_type');
        $daysIn   = (int) ($request->input('days') ?: 90);

        $days  = max(1, min(3650, $daysIn));
        $since = now()->subDays($days);

        // ====== SELECT dinamis ======
        $selects = [
            DB::raw('MAX(ar.id) as id'),
            'ar.doc_type',
            DB::raw('MAX(ar.order_index) as order_index'),
            DB::raw('MAX(ar.signed_at) as signed_at'),
            DB::raw('MAX(ar.signature_qr_path) as signature_qr_path'),
            DB::raw('MAX(ar.signature_payload) as signature_payload'),

            'r.id as rapat_id','r.judul','r.nomor_undangan','r.tanggal','r.waktu_mulai','r.tempat',
            'k.nama as nama_kategori',
        ];

        // kolom kandidat pembuat rapat (bisa salah satu ada)
        $creatorCols = ['dibuat_oleh','created_by','id_user'];
        foreach ($creatorCols as $c) {
            if (Schema::hasColumn('rapat', $c)) {
                // pakai agregat agar tidak perlu ditambahkan ke GROUP BY
                $selects[] = DB::raw("MAX(r.{$c}) as {$c}");
            }
        }

        // ====== base query + dedupe per (rapat, doc_type) ======
        $q = DB::table('approval_requests as ar')
            ->join('rapat as r','r.id','=','ar.rapat_id')
            ->leftJoin('kategori_rapat as k','k.id','=','r.id_kategori')
            ->where('ar.approver_user_id',$uid)
            ->where('ar.status','approved')
            ->where('ar.signed_at','>=',$since)
            ->select($selects)
            ->groupBy('r.id','ar.doc_type','r.judul','r.nomor_undangan','r.tanggal','r.waktu_mulai','r.tempat','k.nama')
            ->orderBy(DB::raw('MAX(ar.signed_at)'), 'desc');

        // ====== filters ======
        if (!empty($kategori))  $q->where('r.id_kategori',$kategori);
        if (!empty($tanggal))   $q->whereDate('r.tanggal',$tanggal);
        if (!empty($docType))   $q->where('ar.doc_type',$docType);
        if (!empty($keyword)) {
            $kw = trim($keyword);
            $q->where(function($w) use ($kw){
                $w->where('r.judul','like',"%{$kw}%")
                  ->orWhere('r.nomor_undangan','like',"%{$kw}%")
                  ->orWhere('r.tempat','like',"%{$kw}%");
            });
        }

        // pagination
        $rows = $q->paginate(8)->appends($request->query());

        // list kategori buat filter
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        // ====== enrich per baris: nama_pembuat + preview + qr url ======
        $rows->getCollection()->transform(function($r){
            // cari creator_id dari kolom yang tersedia
            $creatorId = null;
            foreach (['dibuat_oleh','created_by','id_user'] as $c) {
                if (property_exists($r, $c) && !is_null($r->{$c})) {
                    $creatorId = $r->{$c};
                    break;
                }
            }
            $r->nama_pembuat = $creatorId
                ? (DB::table('users')->where('id',$creatorId)->value('name') ?: null)
                : null;

            // preview url
            $preview = null;
            if ($r->doc_type === 'notulensi') {
                $nid = DB::table('notulensi')->where('id_rapat',$r->rapat_id)->value('id');
                if ($nid && \Illuminate\Support\Facades\Route::has('notulensi.cetak')) {
                    $preview = route('notulensi.cetak', $nid);
                }
            } elseif ($r->doc_type === 'undangan') {
                if (\Illuminate\Support\Facades\Route::has('rapat.undangan.pdf')) {
                    $preview = route('rapat.undangan.pdf', $r->rapat_id);
                }
            } elseif ($r->doc_type === 'absensi') {
                if (\Illuminate\Support\Facades\Route::has('absensi.preview')) {
                    $preview = route('absensi.preview', $r->rapat_id);
                } elseif (\Illuminate\Support\Facades\Route::has('absensi.export.pdf')) {
                    $preview = route('absensi.export.pdf', $r->rapat_id).'?preview=1';
                }
            }
            $r->preview_url   = $preview;
            $r->qr_public_url = $r->signature_qr_path ? url($r->signature_qr_path) : null;

            return $r;
        });

        $docOptions = [
            '' => 'Semua Dokumen',
            'undangan'  => 'Undangan',
            'notulensi' => 'Notulensi',
            'absensi'   => 'Absensi',
        ];
        $dayOptions = [7=> '7 hari', 30=>'30 hari', 90=>'90 hari', 180=>'180 hari', 365=>'1 tahun'];

        return view('approval.approved', compact('rows','daftar_kategori','docOptions','dayOptions','days'));
    }

    /**
     * Halaman Rapat (untuk approver) mirip peserta.
     */
    public function meetings(Request $request)
    {
        $uid = auth()->id();

        // ====== Base query: rapat yang terkait dengan approver ini ======
        $q = DB::table('rapat as r')
            ->leftJoin('kategori_rapat as k','k.id','=','r.id_kategori')
            ->leftJoin('users as pembuat','pembuat.id','=','r.dibuat_oleh')
            ->join('approval_requests as ar', 'ar.rapat_id', '=', 'r.id')
            ->where('ar.approver_user_id', $uid)
            ->select(
                'r.id','r.nomor_undangan','r.judul','r.tanggal','r.waktu_mulai','r.tempat',
                'k.nama as nama_kategori',
                'pembuat.name as nama_pembuat',
                DB::raw('MIN(ar.order_index) as first_order')
            )
            ->groupBy('r.id','r.nomor_undangan','r.judul','r.tanggal','r.waktu_mulai','r.tempat','k.nama','pembuat.name');

        // ====== Filter ======
        if ($request->filled('kategori')) $q->where('r.id_kategori', $request->kategori);
        if ($request->filled('tanggal'))  $q->whereDate('r.tanggal', $request->tanggal);
        if ($request->filled('keyword')) {
            $kw = $request->keyword;
            $q->where(function($x) use ($kw){
                $x->where('r.judul','like',"%{$kw}%")
                  ->orWhere('r.nomor_undangan','like',"%{$kw}%")
                  ->orWhere('r.tempat','like',"%{$kw}%");
            });
        }

        $daftar_rapat = $q->orderBy('r.tanggal','desc')
            ->paginate(8)->appends($request->query());

        // daftar kategori untuk filter
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        // ====== Hitung “step saya berikutnya” per rapat ======
        $rapatIds = $daftar_rapat->pluck('id')->all();
        $reqs = collect();
        if (!empty($rapatIds)) {
            $reqs = DB::table('approval_requests as ar')
                ->leftJoin('users as u','u.id','=','ar.approver_user_id')
                ->select('ar.*','u.name as approver_name')
                ->whereIn('ar.rapat_id',$rapatIds)
                ->where('ar.approver_user_id',$uid)
                ->orderBy('ar.doc_type')->orderBy('ar.order_index')
                ->get();
        }

        $nextOpen = [];
        foreach ($reqs->groupBy('rapat_id') as $rid => $items) {
            $pendingMine = $items->where('status','pending')->sortBy('order_index')->values();
            $candidate = null;
            foreach ($pendingMine as $p) {
                $blocked = DB::table('approval_requests')
                    ->where('rapat_id',$p->rapat_id)
                    ->where('doc_type',$p->doc_type)
                    ->where('order_index','<',$p->order_index)
                    ->where('status','!=','approved')
                    ->exists();
                if (!$blocked) { $candidate = $p; break; }
            }
            if ($candidate) $nextOpen[$rid] = $candidate;
        }

        // jumlah peserta undangan
        $counts = [];
        if (!empty($rapatIds)) {
            $rows = DB::table('undangan')
                ->select('id_rapat', DB::raw('COUNT(*) as jml'))
                ->whereIn('id_rapat',$rapatIds)
                ->groupBy('id_rapat')->get();
            foreach ($rows as $r) $counts[$r->id_rapat] = (int)$r->jml;
        }

        return view('approval.rapat', compact('daftar_rapat','daftar_kategori','nextOpen','counts'));
    }

    public function notifyCreatorIfDocFullyApproved(int $rapatId, string $docType, ?string $approvedBy = null): void
{
    // Pastikan TIDAK ada approval yang belum approved
    $open = DB::table('approval_requests')
        ->where('rapat_id', $rapatId)
        ->where('doc_type', $docType)
        ->where('status', '!=', 'approved')
        ->count();

    if ($open > 0) return;

    $rapat = DB::table('rapat')->where('id', $rapatId)->first();
    if (!$rapat) return;

    // Ambil creator per doc:
    // - notulensi -> notulensi.id_user (notulis)
    // - undangan/absensi -> rapat.dibuat_oleh
    if ($docType === 'notulensi') {
        $creator = DB::table('notulensi')
            ->join('users','users.id','=','notulensi.id_user')
            ->where('notulensi.id_rapat',$rapatId)
            ->orderByDesc('notulensi.id')
            ->select('users.name','users.no_hp')
            ->first();
    } else {
        $creator = DB::table('rapat')
            ->join('users','users.id','=','rapat.dibuat_oleh')
            ->where('rapat.id',$rapatId)
            ->select('users.name','users.no_hp')
            ->first();
    }
    if (!$creator || empty($creator->no_hp)) return;

    // Normalisasi nomor
    $wa = preg_replace('/\D+/', '', (string)$creator->no_hp);
    $wa = preg_replace('/^0/', '62', $wa);

    $judul   = $rapat->judul ?? 'Dokumen';
    $label   = strtoupper($docType);  // NOTULENSI | UNDANGAN | ABSENSI
    $tail    = $approvedBy ? " (disetujui terakhir oleh {$approvedBy})" : "";

    $msg =
        "Assalamu’alaikum Wr. Wb.\n\n".
        "Dengan hormat,\n".
        "Kami informasikan bahwa dokumen {$label} untuk rapat berikut telah *disetujui sepenuhnya*{$tail}:\n\n".
        "📄 {$judul}\n\n".
        "Terima kasih atas kerja sama dan perhatiannya.\n".
        "Wassalamu’alaikum Wr. Wb.";

    try {
        \App\Helpers\FonnteWa::send($wa, $msg);
        Log::info("[WA] FULL-APPROVED notice sent to creator {$wa} ({$docType}, rapat={$rapatId})");
    } catch (\Throwable $e) {
        Log::error("[WA] Failed notify creator ({$docType}, rapat={$rapatId}): ".$e->getMessage());
    }
}

    private function extractPhoneNumberByUserId(int $userId): ?string
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) return null;
        return $this->extractPhoneNumberFromRow($user);
    }

    private function extractPhoneNumberFromRow($row): ?string
    {
        if (!$row) return null;
        $fields = ['no_hp','phone','telp','telepon','hp','whatsapp','wa','no_telp','no_wa'];
        foreach ($fields as $field) {
            if (!empty($row->{$field})) {
                $normalized = FonnteWa::normalizeNumber($row->{$field});
                if ($normalized) return $normalized;
            }
        }
        return null;
    }
}
