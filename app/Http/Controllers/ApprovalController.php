<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
     * Proses setuju & generate QR (tanpa Imagick, pakai GD).
     * - Simpan PNG ke public/qr/...
     * - Update approval_requests
     * - Push notifikasi ke approver berikutnya jika ada
     * - Kalau step terakhir, tandai rapat.<doc_type>_approved_at
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

    // 4) Generate QR via Google Chart API (tanpa imagick/GD)
    //    Docs: https://chart.googleapis.com/chart?chs=220x220&cht=qr&chl=ENCODED
    $encoded = urlencode($payloadJson);
    $qrUrl   = "https://chart.googleapis.com/chart?chs=220x220&cht=qr&chl={$encoded}";

    // Siapkan folder & nama file
    $qrDir = public_path('qr');
    if (!is_dir($qrDir)) {
        @mkdir($qrDir, 0755, true);
    }
    $basename     = 'qr_' . $req->doc_type . '_r' . $req->rapat_id . '_a' . $approver->id . '_' . Str::random(6) . '.png';
    $relativePath = 'qr/' . $basename;
    $absolutePath = public_path($relativePath);

    // Unduh PNG ke public/qr
    // Catatan: butuh allow_url_fopen aktif. Jika server disable, ganti dengan Guzzle.
    $pngData = @file_get_contents($qrUrl);
    if ($pngData === false) {
        // fallback server lain (opsional)
        $alt = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={$encoded}";
        $pngData = @file_get_contents($alt);
    }
    if ($pngData === false) {
        return back()->with('error', 'Gagal membuat QR (akses internet/allow_url_fopen nonaktif).');
    }
    file_put_contents($absolutePath, $pngData);

    // 5) Simpan hasil signature ke DB (pakai kolom kamu)
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
        $col = $req->doc_type . '_approved_at'; // contoh: undangan_approved_at
        if (Schema::hasColumn('rapat', $col)) {
            DB::table('rapat')->where('id', $req->rapat_id)->update([$col => now()]);
        }
    }

    return redirect()->route('approval.done', ['token' => $token])
        ->with('success', 'Approval berhasil & QR dibuat (tanpa Imagick).');
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
}
