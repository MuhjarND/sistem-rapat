<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    /**
     * Verifikasi QR yang dipindai.
     * Mendukung 2 format:
     * - d=<base64(JSON payload)>
     * - data=<raw JSON>   (fallback utk QR lama)
     */
    public function verify(Request $request)
    {
        // 1) Ambil data dari query
        $raw = null;

        // Prioritas: ?d=base64
        if ($request->filled('d')) {
            $raw = base64_decode($request->query('d'), true);
        }

        // Fallback: ?data=JSON mentah
        if (!$raw && $request->filled('data')) {
            $raw = $request->query('data');
        }

        // Fallback terakhir: kalau tidak ada apa-apa, tampilkan halaman bantuan
        if (!$raw) {
            return view('qr.verify_result', [
                'valid'   => false,
                'reason'  => 'Tidak ada payload untuk diverifikasi. QR harus berisi parameter ?d=base64(JSON) atau ?data=JSON.',
                'summary' => null,
            ]);
        }

        // 2) Parse JSON
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return view('qr.verify_result', [
                'valid'   => false,
                'reason'  => 'Payload bukan JSON yang valid.',
                'summary' => null,
            ]);
        }

        // 3) Ambil & lepaskan signature
        $sig = $decoded['sig'] ?? null;
        if (!$sig) {
            return view('qr.verify_result', [
                'valid'   => false,
                'reason'  => 'Signature (sig) tidak ditemukan di payload.',
                'summary' => null,
            ]);
        }
        unset($decoded['sig']); // recompute tanpa sig

        // 4) Hitung ulang HMAC pakai APP_KEY
        $secret = config('app.key');
        if (is_string($secret) && Str::startsWith($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        // Penting: pakai opsi encoding yang sama dengan saat tanda tangan dibuat
        $canonical = json_encode($decoded, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $recomputed = hash_hmac('sha256', $canonical, $secret);

        if (!hash_equals($sig, $recomputed)) {
            return view('qr.verify_result', [
                'valid'   => false,
                'reason'  => 'Signature tidak cocok (payload telah berubah atau kunci salah).',
                'summary' => null,
            ]);
        }

        // 5) Cross-check ke database (approval_requests)
        $docType = $decoded['doc_type'] ?? null;
        $rapatId = $decoded['rapat_id'] ?? null;
        $approverId = data_get($decoded, 'approver.id');

        if (!$docType || !$rapatId || !$approverId) {
            return view('qr.verify_result', [
                'valid'   => false,
                'reason'  => 'Field wajib (doc_type/rapat_id/approver.id) tidak lengkap.',
                'summary' => null,
            ]);
        }

        // Cari record approval yang sesuai & sudah approved
        $row = DB::table('approval_requests')
            ->where('rapat_id', $rapatId)
            ->where('doc_type', $docType)
            ->where('approver_user_id', $approverId)
            ->where('status', 'approved')
            ->orderByDesc('signed_at')
            ->first();

        if (!$row) {
            return view('qr.verify_result', [
                'valid'   => false,
                'reason'  => 'Tidak ditemukan catatan approval yang sah untuk payload ini.',
                'summary' => null,
            ]);
        }

        // **** KETAT: cocokkan payload yang ditandatangani ****
        $rowPayload = $row->signature_payload ?? $row->qr_payload ?? null; // dukung nama kolom berbeda
        if (!$rowPayload) {
            // Kalau kolom payload tidak disimpan, setidaknya cocokkan sig
            // (opsional) kamu bisa simpan sig terpisah di DB
        } else {
            // Bandingkan canonical tanpa sig; supaya perbedaan spacing tidak ngaruh
            $rowArr = json_decode($rowPayload, true);
            if (!is_array($rowArr) || !isset($rowArr['sig'])) {
                return view('qr.verify_result', [
                    'valid'   => false,
                    'reason'  => 'Payload di database tidak valid.',
                    'summary' => null,
                ]);
            }
            $rowSig = $rowArr['sig'];
            unset($rowArr['sig']);
            $rowCanonical = json_encode($rowArr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

            if (!hash_equals($rowSig, $sig) || !hash_equals($rowCanonical, $canonical)) {
                return view('qr.verify_result', [
                    'valid'   => false,
                    'reason'  => 'Payload QR tidak cocok dengan yang tercatat di database.',
                    'summary' => null,
                ]);
            }
        }

        // 6) (Opsional) Validasi tambahan:
        // - revoked? expired? (misal 2 tahun kedaluwarsa)
        // - file QR masih ada?
        $qrPath = $row->signature_qr_path ?? $row->qr_path ?? null;
        $fileExists = $qrPath ? file_exists(public_path($qrPath)) : false;

        // 7) Ambil ringkasan untuk ditampilkan
        $rapat = DB::table('rapat')->where('id', $rapatId)->first();
        $approver = DB::table('users')->where('id', $approverId)->first();

        $summary = [
            'doc_type'   => ucfirst($docType),
            'nomor'      => $decoded['nomor'] ?? ($rapat->nomor_undangan ?? '-'),
            'judul'      => $decoded['judul'] ?? ($rapat->judul ?? '-'),
            'tanggal'    => $decoded['tanggal'] ?? ($rapat->tanggal ?? '-'),
            'approver'   => $approver ? ($approver->name . ($approver->jabatan ? ' - '.$approver->jabatan : '')) : ('ID '.$approverId),
            'order'      => data_get($decoded, 'approver.order', '-'),
            'signed_at'  => $row->signed_at ?? '-',
            'file_qr_ok' => $fileExists ? 'Ada' : 'Tidak ada',
        ];

        return view('qr.verify_result', [
            'valid'   => true,
            'reason'  => null,
            'summary' => $summary,
        ]);
    }
}
