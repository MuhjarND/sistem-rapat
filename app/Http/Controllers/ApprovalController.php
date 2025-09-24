<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ApprovalController extends Controller
{
    /**
     * Tampilkan semua approval pending milik user login.
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

        // tandai apakah step ini terblokir karena step sebelumnya belum approve
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
            ->select('ar.*', 'r.judul', 'r.nomor_undangan', 'r.tanggal', 'r.waktu_mulai', 'r.tempat', 'u.name as approver_name')
            ->where('ar.sign_token', $token)
            ->first();

        if (!$req) abort(404);

        // cek apakah step ini boleh diproses (step sebelumnya harus approved)
        $blocked = DB::table('approval_requests')
            ->where('rapat_id', $req->rapat_id)
            ->where('doc_type', $req->doc_type)
            ->where('order_index', '<', $req->order_index)
            ->where('status', '!=', 'approved')
            ->exists();

        return view('approval.sign', compact('req', 'blocked'));
    }

    /**
     * Proses setuju & generate QR (dengan logo jika ada).
     * - Simpan PNG ke public/qr/...
     * - Update approval_requests
     * - Push notifikasi ke approver berikutnya jika ada
     * - Kalau step terakhir, tandai rapat.<doc_type>_approved_at
     * - Jika doc_type = 'undangan' selesai, otomatis generate QR 'absensi' (unik & berbeda)
     */
    public function signSubmit(Request $request, $token)
    {
        // 1) Ambil request approval
        $req = DB::table('approval_requests')->where('sign_token', $token)->first();
        if (!$req) abort(404);
        if ($req->status === 'approved') {
            return redirect()->route('approval.done', ['token' => $token])
                ->with('success', 'Dokumen ini sudah disetujui sebelumnya.');
        }

        // 2) Cek urutan (step sebelumnya harus approved)
        $blocked = DB::table('approval_requests')
            ->where('rapat_id', $req->rapat_id)
            ->where('doc_type', $req->doc_type)
            ->where('order_index', '<', $req->order_index)
            ->where('status', '!=', 'approved')
            ->exists();
        if ($blocked) {
            return back()->with('error', 'Tahap sebelum Anda belum selesai.');
        }

        // 3) Payload + HMAC (schema konsisten: approver)
        $rapat    = DB::table('rapat')->where('id', $req->rapat_id)->first();
        $approver = DB::table('users')->where('id', $req->approver_user_id)->first();

        $payload = [
            'v'         => 1,
            'doc_type'  => $req->doc_type, // undangan | absensi | notulensi
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

        // 4) Generate QR berisi URL verifikasi + tempel logo
        $qrContent = route('qr.verify', ['d' => base64_encode($payloadJson)]);

        // Siapkan folder & nama file
        $qrDir = public_path('qr');
        if (!is_dir($qrDir)) {
            @mkdir($qrDir, 0755, true);
        }
        $basename     = 'qr_' . $req->doc_type . '_r' . $req->rapat_id . '_a' . $approver->id . '_' . Str::random(6) . '.png';
        $relativePath = 'qr/' . $basename;
        $absolutePath = public_path($relativePath);

        // Simpan QR + logo (ECC High; jika GD tidak ada, fallback PNG biasa)
        $ok = $this->saveQrWithLogo($qrContent, $absolutePath, 600, public_path('logo_qr.png'));
        if (!$ok) {
            return back()->with('error', 'Gagal membuat QR (akses internet/allow_url_fopen nonaktif).');
        }

        // 5) Simpan hasil signature ke DB
        DB::table('approval_requests')->where('id', $req->id)->update([
            'status'             => 'approved',
            'signature_qr_path'  => $relativePath,
            'signature_payload'  => $payloadJson,
            'signed_at'          => now(),
            'updated_at'         => now(),
        ]);

        // 6) Teruskan ke approver berikutnya atau tandai selesai
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
            // doc_type ini selesai
            $col = $req->doc_type . '_approved_at';
            if (Schema::hasColumn('rapat', $col)) {
                DB::table('rapat')->where('id', $req->rapat_id)->update([$col => now()]);
            }

            // Bila UNDANGAN selesai -> otomatis buat QR ABSENSI (unik & berbeda, plus logo)
            if ($req->doc_type === 'undangan') {
                $this->generateAbsensiQr((int)$req->rapat_id);
                // Atau sinkron dengan AbsensiController kalau kamu pakai ensure...:
                app(\App\Http\Controllers\AbsensiController::class)
                    ->ensureAbsensiQrMirrorsUndangan((int) $req->rapat_id);
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
     * Generate QR khusus dokumen ABSENSI (dipanggil otomatis saat undangan selesai).
     * - Buat payload baru (doc_type='absensi', nonce baru, issued_at baru, sig baru)
     * - Simpan PNG ke public/qr dengan logo
     * - Upsert approval_requests (doc_type='absensi', status=approved) per approver
     */
    private function generateAbsensiQr(int $rapatId): void
    {
        $rapat = DB::table('rapat')->where('id', $rapatId)->first();
        if (!$rapat) return;

        // daftar approver: approval1 wajib, approval2 opsional
        $approvers = [];
        if (!empty($rapat->approval1_user_id)) {
            $approvers[] = (int)$rapat->approval1_user_id;
        }
        if (!empty($rapat->approval2_user_id)) {
            $approvers[] = (int)$rapat->approval2_user_id;
        }

        $secret = config('app.key');
        if (is_string($secret) && Str::startsWith($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        foreach ($approvers as $idx => $uid) {
            $user = DB::table('users')->where('id', $uid)->first();
            if (!$user) continue;

            // payload baru KHUSUS absensi (unik)
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

            // QR berisi URL verifikasi
            $qrContent = route('qr.verify', ['d' => base64_encode($payloadJson)]);

            // siapkan folder & file
            $qrDir = public_path('qr');
            if (!is_dir($qrDir)) {
                @mkdir($qrDir, 0755, true);
            }
            $basename     = 'qr_absensi_r'.$rapat->id.'_a'.$user->id.'_'.Str::random(6).'.png';
            $relativePath = 'qr/'.$basename;
            $absolutePath = public_path($relativePath);

            // Simpan QR + logo
            $ok = $this->saveQrWithLogo($qrContent, $absolutePath, 600, public_path('logo_qr.png'));
            if (!$ok) {
                Log::warning('[absensi-qr] gagal membuat QR: rapat '.$rapat->id.' user '.$user->id);
                continue;
            }

            // Upsert approval_requests untuk doc_type 'absensi' -> approved
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
                    'status'            => 'approved', // langsung approved otomatis
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
     * Helper: buat & simpan QR dengan logo (opsional).
     * - $qrContent   : string yang akan di-encode (di kita: URL verifikasi)
     * - $absolutePath: path file output PNG
     * - $sizePx      : ukuran QR (px)
     * - $logoPath    : path logo PNG transparan (opsional)
     *
     * Menggunakan Google Chart (fallback ke qrserver) + overlay logo via GD jika tersedia.
     * ECC diset High (H) agar logo aman.
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

        // Jika GD tersedia, tempel logo
        if (function_exists('imagecreatefromstring') && function_exists('imagepng')) {
            $qrImg = @imagecreatefromstring($png);
            if ($qrImg !== false) {
                if ($logoPath && is_file($logoPath)) {
                    $logoImg = @imagecreatefrompng($logoPath); // harus PNG (transparan)
                    if ($logoImg !== false) {
                        // jaga alpha
                        imagealphablending($logoImg, true);
                        imagesavealpha($logoImg, true);

                        $qrW = imagesx($qrImg); $qrH = imagesy($qrImg);
                        $lw  = imagesx($logoImg); $lh = imagesy($logoImg);

                        // target lebar logo ~18% lebar QR (aman untuk ECC H)
                        $targetW = (int) round($qrW * 0.18);
                        $targetH = (int) round($lh * ($targetW / $lw));
                        $dstX = (int) round(($qrW - $targetW) / 2);
                        $dstY = (int) round(($qrH - $targetH) / 2);

                        // resize logo dengan alpha
                        $logoResized = imagecreatetruecolor($targetW, $targetH);
                        imagealphablending($logoResized, false);
                        imagesavealpha($logoResized, true);
                        imagecopyresampled($logoResized, $logoImg, 0, 0, 0, 0, $targetW, $targetH, $lw, $lh);

                        // tumpuk ke QR
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
            // fallback: simpan PNG apa adanya
            @file_put_contents($absolutePath, $png);
            $saved = true;
        }

        return $saved;
    }
}
