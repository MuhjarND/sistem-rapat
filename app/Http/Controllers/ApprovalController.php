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
     * Helper: buat QR dari konten (URL verifikasi), lalu tempel logo di tengah (GD, tanpa Imagick).
     * - $qrContent : string yang akan di-encode ke QR (kita pakai URL verifikasi)
     * - $savePathAbs : path absolut tempat simpan PNG final
     * - $logoAbsPath : path absolut logo (PNG/JPG/GIF). Boleh null / tidak ada -> QR tanpa logo
     * - $size : ukuran sisi QR sumber dari service (px)
     * return bool sukses
     */
private function makeQrWithLogo(string $qrContent, string $savePathAbs, ?string $logoAbsPath, int $size = 420): bool
{
    // 1) Ambil QR dasar
    $encoded = urlencode($qrContent);
    $src1 = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded}";
    $png  = @file_get_contents($src1);
    if ($png === false) {
        $src2 = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded}&margin=2";
        $png  = @file_get_contents($src2);
        if ($png === false) return false;
    }
    $tmp = sys_get_temp_dir().'/qr_'.uniqid().'.png';
    @file_put_contents($tmp, $png);

    $qr = @imagecreatefrompng($tmp);
    @unlink($tmp);
    if (!$qr) return false;
    imagesavealpha($qr, true);
    imagealphablending($qr, true);

    // 2) Logo (opsional)
    if ($logoAbsPath && is_file($logoAbsPath)) {
        $logo = null;
        $type = @exif_imagetype($logoAbsPath);
        if     ($type === IMAGETYPE_PNG)  $logo = @imagecreatefrompng($logoAbsPath);
        elseif ($type === IMAGETYPE_JPEG) $logo = @imagecreatefromjpeg($logoAbsPath);
        elseif ($type === IMAGETYPE_GIF)  $logo = @imagecreatefromgif($logoAbsPath);

        if ($logo) {
            imagesavealpha($logo, true);
            imagealphablending($logo, true);

            // Target ukuran logo ~22% dari lebar QR
            $qrW = imagesx($qr);  $qrH = imagesy($qr);
            $lgW = imagesx($logo);$lgH = imagesy($logo);
            $targetW = (int) floor($qrW * 0.22);
            $scale   = $targetW / max(1, $lgW);
            $targetH = (int) floor($lgH * $scale);

            // Resize ke canvas transparan
            $logoRes = imagecreatetruecolor($targetW, $targetH);
            imagesavealpha($logoRes, true);
            imagealphablending($logoRes, false); // penting agar setpixel alpha efektif
            $transparent = imagecolorallocatealpha($logoRes, 0, 0, 0, 127);
            imagefill($logoRes, 0, 0, $transparent);

            imagecopyresampled($logoRes, $logo, 0, 0, 0, 0, $targetW, $targetH, $lgW, $lgH);
            imagedestroy($logo);

            // 3) Ubah white-ish → alpha (hilangkan background putih)
            $threshold = 245; // 0..255 (semakin kecil, semakin agresif)
            for ($y = 0; $y < $targetH; $y++) {
                for ($x = 0; $x < $targetW; $x++) {
                    $col = imagecolorat($logoRes, $x, $y);
                    // dukung truecolor dgn alpha
                    $r = ($col >> 16) & 0xFF;
                    $g = ($col >> 8)  & 0xFF;
                    $b = ($col)       & 0xFF;
                    $a = ($col & 0x7F000000) >> 24; // 0..127 (0=opaque)

                    // Jika sudah transparan, skip
                    if ($a >= 1) continue;

                    // Jika cukup putih → set transparan penuh
                    if ($r >= $threshold && $g >= $threshold && $b >= $threshold) {
                        $newCol = imagecolorallocatealpha($logoRes, $r, $g, $b, 127);
                        imagesetpixel($logoRes, $x, $y, $newCol);
                    }
                }
            }
            imagealphablending($logoRes, true); // kembali normal

            // 4) Tempel ke pusat QR
            $dstX = (int) floor(($qrW - $targetW) / 2);
            $dstY = (int) floor(($qrH - $targetH) / 2);
            imagecopy($qr, $logoRes, $dstX, $dstY, 0, 0, $targetW, $targetH);
            imagedestroy($logoRes);
        }
    }

    // 5) Simpan PNG final
    $ok = @imagepng($qr, $savePathAbs, 6);
    imagedestroy($qr);
    return (bool) $ok;
}
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
     * Proses setuju & generate QR (tanpa Imagick).
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

        // 3) Payload + HMAC
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

        // 4) === Generate QR berlogo ===
        //    ISI QR = URL verifikasi agar saat scan langsung membuka halaman verifikasi
        $qrContent = route('qr.verify', ['d' => base64_encode($payloadJson)]);

        // Siapkan folder & nama file
        $qrDir = public_path('qr');
        if (!is_dir($qrDir)) {
            @mkdir($qrDir, 0755, true);
        }
        $basename     = 'qr_' . $req->doc_type . '_r' . $req->rapat_id . '_a' . $approver->id . '_' . Str::random(6) . '.png';
        $relativePath = 'qr/' . $basename;
        $absolutePath = public_path($relativePath);

        // Path logo (opsional)
        $logoPath = public_path('logo_qr.png');
        $logoAbs  = is_file($logoPath) ? $logoPath : null;

        // Buat file QR + logo
        $ok = $this->makeQrWithLogo($qrContent, $absolutePath, $logoAbs, 420);
        if (!$ok) {
            return back()->with('error', 'Gagal membuat QR (berlogo). Pastikan ekstensi GD aktif.');
        }

        // 5) Simpan hasil signature ke DB
        DB::table('approval_requests')->where('id', $req->id)->update([
            'status'             => 'approved',
            'signature_qr_path'  => $relativePath,   // contoh: 'qr/qr_undangan_r12_a5_xxxxxx.png'
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
            $col = $req->doc_type . '_approved_at'; // contoh: undangan_approved_at
            if (Schema::hasColumn('rapat', $col)) {
                DB::table('rapat')->where('id', $req->rapat_id)->update([$col => now()]);
            }

            // Bila UNDANGAN selesai -> otomatis buat QR ABSENSI (unik & berbeda)
            if ($req->doc_type === 'undangan') {
                $this->generateAbsensiQr((int)$req->rapat_id);

                // Opsional: panggil mirror ke controller Absensi jika kamu pakai fungsi itu juga
                app(\App\Http\Controllers\AbsensiController::class)
                    ->ensureAbsensiQrMirrorsUndangan((int) $req->rapat_id);
            }
        }

        return redirect()->route('approval.done', ['token' => $token])
            ->with('success', 'Approval berhasil & QR berlogo dibuat (berisi URL verifikasi).');
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
     * - Simpan PNG ke public/qr (berlogo)
     * - Upsert approval_requests (doc_type='absensi', status=approved) per approver
     */
    private function generateAbsensiQr(int $rapatId): void
    {
        $rapat = DB::table('rapat')->where('id', $rapatId)->first();
        if (!$rapat) return;

        // daftar approver: approval1 wajib, approval2 opsional
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

            // payload unik ABSENSI
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
            if (!is_dir($qrDir)) @mkdir($qrDir, 0755, true);
            $basename     = 'qr_absensi_r'.$rapat->id.'_a'.$user->id.'_'.Str::random(6).'.png';
            $relativePath = 'qr/'.$basename;
            $absolutePath = public_path($relativePath);

            // logo (opsional)
            $logoPath = public_path('logo_qr.png');
            $logoAbs  = is_file($logoPath) ? $logoPath : null;

            // Buat QR + logo
            $ok = $this->makeQrWithLogo($qrContent, $absolutePath, $logoAbs, 420);
            if (!$ok) {
                Log::warning('[absensi-qr] gagal membuat QR berlogo: rapat '.$rapat->id.' user '.$user->id);
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
                    'status'            => 'approved', // otomatis
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
}
