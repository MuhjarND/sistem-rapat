<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route as RouteFacade;

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

        // Ringkasan per doc_type
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

        $metrics = [
            'pending_total'  => $pending->count(),
            'open_total'     => $pendingOpen->count(),
            'blocked_total'  => $pendingBlocked->count(),
            'approved_30d'   => $recentApproved->count(),
            'today_to_sign'  => $pendingOpen->where('tanggal', '>=', $today)->count(),
            'by_type'        => [
                'undangan' => (int) ($byType['undangan'] ?? 0),
                'notulensi'=> (int) ($byType['notulensi'] ?? 0),
                'absensi'  => (int) ($byType['absensi'] ?? 0),
            ],
        ];

        return view('approval.dashboard', compact('pendingOpen','pendingBlocked','recentApproved','metrics'));
    }

    /**
     * List pending milik user.
     */
    public function pending()
    {
        $userId = auth()->id();

        $rows = DB::table('approval_requests as ar')
            ->join('rapat as r', 'ar.rapat_id', '=', 'r.id')
            ->select(
                'ar.id','ar.doc_type','ar.order_index','ar.status','ar.sign_token',
                'ar.rapat_id','r.judul','r.nomor_undangan','r.tanggal','r.waktu_mulai','r.tempat'
            )
            ->where('ar.approver_user_id', $userId)
            ->where('ar.status', 'pending')
            ->orderBy('ar.created_at')
            ->orderBy('ar.order_index')
            ->get();

        $rows = $rows->map(function ($row) {
            $row->blocked = DB::table('approval_requests')
                ->where('rapat_id', $row->rapat_id)
                ->where('doc_type', $row->doc_type)
                ->where('order_index', '<', $row->order_index)
                ->where('status', '!=', 'approved')
                ->exists();
            return $row;
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
                $previewUrl = route('absensi.export.pdf', $req->rapat_id);
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
            $nextApprover = DB::table('users')->where('id', $next->approver_user_id)->first();
            if ($nextApprover && $nextApprover->no_hp) {
                $wa = preg_replace('/^0/', '62', $nextApprover->no_hp);
                $signUrl = url('/approval/sign/'.$next->sign_token);
                \App\Helpers\FonnteWa::send($wa, "Mohon approval {$req->doc_type} rapat: {$rapat->judul}\nLink: {$signUrl}");
            }
        } else {
            // step terakhir: tandai selesai
            $col = $req->doc_type . '_approved_at';
            if (Schema::hasColumn('rapat', $col)) {
                DB::table('rapat')->where('id', $req->rapat_id)->update([$col => now()]);
            }

            // Undangan selesai -> buat QR absensi + kirim WA ke peserta  [NEW]
            if ($req->doc_type === 'undangan') {
                $this->generateAbsensiQr((int)$req->rapat_id);
                app(\App\Http\Controllers\AbsensiController::class)
                    ->ensureAbsensiQrMirrorsUndangan((int) $req->rapat_id);

                // [NEW] Kirim WA peserta setelah undangan final-approved
                $this->notifyParticipantsOnInvitationApproved($rapat);
            }
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
     * (OPSIONAL) Buka blokir step-step berikutnya setelah kreator mengirim revisi.
     */
    public function unblockNextSteps(int $rapatId, string $docType, int $fromOrderIndex = 0): void
    {
        // 1) Reset rapat.<doc_type>_rejected_at ke NULL (jika kolom ada)
        $col = $docType . '_rejected_at';
        if (Schema::hasColumn('rapat', $col)) {
            DB::table('rapat')->where('id', $rapatId)->update([$col => null]);
        }

        // 2) Ubah semua step setelah $fromOrderIndex yang statusnya 'blocked' → 'pending'
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

    $previewLink = null;
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
      . "Dengan hormat, kami sampaikan bahwa undangan rapat berikut telah *disetujui oleh seluruh pihak terkait* dan siap untuk dilaksanakan:\n\n"
      . "• Nomor: *{$nomor}*\n"
      . "• Judul: *{$judul}*\n"
      . "• Hari/Tanggal: *{$tanggal}*\n"
      . "• Waktu: *{$waktu} WIB*\n"
      . "• Tempat: *{$tempat}*\n\n"
      . "Silakan meninjau detail rapat melalui tautan berikut:\n"
      . ($previewLink ? "🔗 *Preview Rapat:* {$previewLink}\n" : "")
      . ($pdfLink ? "📄 *Undangan (PDF):* {$pdfLink}\n" : "")
      . "\nAtas perhatian dan kehadirannya kami ucapkan terima kasih.\n\n"
      . "Wassalamu’alaikum warahmatullahi wabarakatuh.\n\n"
      . "*Sekretariat Rapat*";

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


}
